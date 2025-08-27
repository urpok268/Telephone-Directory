<?php
/**
 * LDAP авторизация. Требует установленного php-ldap.
 * Настройки берутся из app/ldap_config.php
 */
function ldap_try_auth(string $username, string $password): ?array {
    $cfg = require __DIR__ . '/ldap_config.php';
    if (empty($cfg['enabled'])) {
        return null;
    }
    if ($username === '' || $password === '') {
        return null;
    }

    $host = $cfg['host'] ?? '';
    $port = (int)($cfg['port'] ?? 389);
    $use_starttls = !empty($cfg['use_starttls']);
    $base_dn = $cfg['base_dn'] ?? '';
    $user_filter_tpl = $cfg['user_filter'] ?? '(sAMAccountName=%s)';
    $upn_domain = $cfg['upn_domain'] ?? '';
    $netbios = $cfg['netbios'] ?? '';
    $bind_dn = $cfg['bind_dn'] ?? null;
    $bind_password = $cfg['bind_password'] ?? null;
    $allowed_groups = $cfg['allowed_groups'] ?? [];

    if (!function_exists('ldap_connect')) {
        // php-ldap не установлен
        return null;
    }

    $conn = @ldap_connect($host, $port);
    if (!$conn) {
        return null;
    }

    // Опции
    @ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    @ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
    if (!empty($cfg['network_timeout'])) {
        @ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, (int)$cfg['network_timeout']);
    }

    // STARTTLS при необходимости
    if ($use_starttls) {
        if (!@ldap_start_tls($conn)) {
            @ldap_unbind($conn);
            return null;
        }
    }

    $user_dn = null;

    // 1) Если есть svc-аккаунт — биндимся им и ищем DN пользователя
    if ($bind_dn && $bind_password) {
        if (!@ldap_bind($conn, $bind_dn, $bind_password)) {
            @ldap_unbind($conn);
            return null;
        }
        $user_filter = sprintf($user_filter_tpl, ldap_escape($username, '', LDAP_ESCAPE_FILTER));
        $search = @ldap_search($conn, $base_dn, $user_filter, ['dn', $cfg['attr_username'] ?? 'sAMAccountName', $cfg['attr_name'] ?? 'cn', $cfg['attr_email'] ?? 'mail', 'memberOf']);
        if (!$search) {
            @ldap_unbind($conn);
            return null;
        }
        $entries = @ldap_get_entries($conn, $search);
        if (!$entries || $entries['count'] < 1) {
            @ldap_unbind($conn);
            return null;
        }
        $user_dn = $entries[0]['dn'] ?? null;
        if (!$user_dn) {
            @ldap_unbind($conn);
            return null;
        }
        // Теперь пробуем bind уже пользователем
        if (!@ldap_bind($conn, $user_dn, $password)) {
            @ldap_unbind($conn);
            return null;
        }
        $entry = $entries[0];

        // Проверка групп (если настроены)
        if (is_array($allowed_groups) && count($allowed_groups) > 0) {
            $memberOf = [];
            if (!empty($entry['memberof'])) {
                for ($i = 0; $i < $entry['memberof']['count']; $i++) {
                    $memberOf[] = $entry['memberof'][$i];
                }
            }
            $ok = false;
            foreach ($allowed_groups as $g) {
                if (in_array($g, $memberOf, true)) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                @ldap_unbind($conn);
                return null;
            }
        }

        $usernameAttr = $cfg['attr_username'] ?? 'sAMAccountName';
        $nameAttr = $cfg['attr_name'] ?? 'cn';
        $mailAttr = $cfg['attr_email'] ?? 'mail';
        $res = [
            'username' => first_attr($entry, $usernameAttr) ?? $username,
            'name'     => first_attr($entry, $nameAttr) ?? $username,
            'email'    => first_attr($entry, $mailAttr) ?? '',
            'dn'       => $user_dn,
        ];
        @ldap_unbind($conn);
        return $res;
    }

    // 2) Иначе пробуем прямой bind пользователем с разными формами имени
    $candidates = [$username];
    if ($upn_domain) {
        $candidates[] = $username . '@' . $upn_domain;
    }
    if ($netbios) {
        $candidates[] = $netbios . '\\' . $username;
    }

    foreach ($candidates as $bind_user) {
        if (@ldap_bind($conn, $bind_user, $password)) {
            // При успешном bind можно попытаться подтянуть атрибуты по поиску
            $attrs = ['dn'];
            $usernameAttr = $cfg['attr_username'] ?? 'sAMAccountName';
            $nameAttr = $cfg['attr_name'] ?? 'cn';
            $mailAttr = $cfg['attr_email'] ?? 'mail';
            foreach ([$usernameAttr, $nameAttr, $mailAttr, 'memberOf'] as $a) {
                if ($a && !in_array($a, $attrs, true)) $attrs[] = $a;
            }

            $resEntry = null;
            if ($base_dn) {
                $f = sprintf($user_filter_tpl, ldap_escape($username, '', LDAP_ESCAPE_FILTER));
                $sr = @ldap_search($conn, $base_dn, $f, $attrs);
                if ($sr) {
                    $entries = @ldap_get_entries($conn, $sr);
                    if ($entries && $entries['count'] >= 1) {
                        $resEntry = $entries[0];
                    }
                }
            }

            // Проверка групп (если настроены)
            if ($resEntry && is_array($allowed_groups) && count($allowed_groups) > 0) {
                $memberOf = [];
                if (!empty($resEntry['memberof'])) {
                    for ($i = 0; $i < $resEntry['memberof']['count']; $i++) {
                        $memberOf[] = $resEntry['memberof'][$i];
                    }
                }
                $ok = false;
                foreach ($allowed_groups as $g) {
                    if (in_array($g, $memberOf, true)) {
                        $ok = true;
                        break;
                    }
                }
                if (!$ok) {
                    @ldap_unbind($conn);
                    return null;
                }
            }

            $res = [
                'username' => $resEntry ? (first_attr($resEntry, $usernameAttr) ?? $username) : $username,
                'name'     => $resEntry ? (first_attr($resEntry, $nameAttr) ?? $username) : $username,
                'email'    => $resEntry ? (first_attr($resEntry, $mailAttr) ?? '') : '',
                'dn'       => $resEntry['dn'] ?? null,
            ];
            @ldap_unbind($conn);
            return $res;
        }
    }

    @ldap_unbind($conn);
    return null;
}

/**
 * Получить первое значение атрибута LDAP в виде строки.
 */
function first_attr($entry, string $attr): ?string {
    $attrLower = strtolower($attr);
    if (!isset($entry[$attrLower])) return null;
    $v = $entry[$attrLower];
    if (is_array($v)) {
        if (isset($v['count']) && $v['count'] > 0) {
            return (string)$v[0];
        }
        return null;
    }
    return (string)$v;
}
