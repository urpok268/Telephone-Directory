<?php
// Настройки LDAP. Заполните под свою среду.
// НИЧЕГО в дизайне не меняется — только бекенд авторизации.
//
// Пример для Active Directory:
return [
    // Включить LDAP-авторизацию
    'enabled' => true,

    // URL LDAP-сервера. Можно указать несколько через массив, но сейчас — одна строка.
    // Примеры: 'ldap://dc01.example.local', 'ldaps://ad.example.com'
    'host' => '',

    // Порт (389 для LDAP/STARTTLS, 636 для LDAPS)
    'port' => 389,

    // Использовать STARTTLS (true/false). Для ldaps:// не требуется.
    'use_starttls' => false,

    // Базовый DN для поиска пользователей
    // Пример: 'DC=example,DC=com'
    'base_dn' => '',

    // Фильтр пользователя; %s будет заменён на введённый логин
    // Для AD обычно: (sAMAccountName=%s) или (userPrincipalName=%s)
    'user_filter' => '(sAMAccountName=%s)',

    // Домен (необязательно). Если указан, попробуем bind как username@domain и DOMAIN\username
    // Пример: 'example.com' (UPN) и/или 'EXAMPLE' (NETBIOS) — можно указать один, либо оба.
    'upn_domain' => 'domain',   // для username@domain
    'netbios'    => 'DOMAIN',       // для DOMAIN\username

    // Техническая учётка (опционально). Если заполнена — сначала привязываемся ей и ищем DN пользователя.
    // Если не заполнена — пробуем bind напрямую учёткой пользователя (через UPN/NETBIOS при наличии домена).
    'bind_dn'       => null, // 'CN=svc_ldap,OU=Service Accounts,DC=example,DC=com'
    'bind_password' => null,

    // Проверка членства в группах (опционально). Если массив не пуст, пользователь должен состоять
    // хотя бы в одной из перечисленных групп (по DN).
    // Пример: ['CN=TelDir Admins,OU=Groups,DC=example,DC=com']
    'allowed_groups' => [],

    // Атрибуты для вытягивания ФИО/почты и т.п. (необязательно)
    'attr_username' => 'sAMAccountName',
    'attr_name'     => 'cn',
    'attr_email'    => 'mail',

    // Таймауты
    'network_timeout' => 5,  // сек
];
