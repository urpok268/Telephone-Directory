// sample data
const orgData = (typeof window !== 'undefined' && window.orgData) || {};
const people = (typeof window !== 'undefined' && window.people) || [];
// --- QR генератор ---
function createQRCodeInContainer(container, text, size){
  container.innerHTML = '';
  if(!text){
    const ph = document.createElement('div');
    ph.style.width = (size-10) + 'px';
    ph.style.height = (size-10) + 'px';
    ph.style.borderRadius = '8px';
    ph.style.background = 'rgba(255,255,255,0.02)';
    ph.style.display = 'flex';
    ph.style.alignItems = 'center';
    ph.style.justifyContent = 'center';
    ph.style.color = 'var(--muted)';
    ph.style.fontSize = '12px';
    ph.textContent = 'Нет телефона';
    container.appendChild(ph);
    return;
  }
  try{
    new QRCode(container, {
      text: text,
      width: size,
      height: size,
      colorDark : "#e6eef6",
      colorLight : "#0b1320",
      correctLevel : QRCode.CorrectLevel.M
    });
  }catch(err){
    console.error('QR generation error', err);
    const ph = document.createElement('div');
    ph.style.width = (size-10) + 'px';
    ph.style.height = (size-10) + 'px';
    ph.style.borderRadius = '8px';
    ph.style.background = 'rgba(255,255,255,0.02)';
    ph.style.display = 'flex';
    ph.style.alignItems = 'center';
    ph.style.justifyContent = 'center';
    ph.style.color = 'var(--muted)';
    ph.style.fontSize = '12px';
    ph.textContent = 'QR error';
    container.appendChild(ph);
  }
}

// --- нормализация телефона ---
function firstPhoneValue(person){
  let p = null;
  if(Array.isArray(person.phones) && person.phones.length) p = person.phones[0];
  else if(person.phone) p = person.phone;
  if(!p) return null;
  p = p.trim().split(/[;,/]/)[0].trim();
  let hasPlus = p.startsWith('+');
  let digits = p.replace(/[^\d]/g,'');
  if(!digits) return null;
  return (hasPlus? '+' : '') + digits;
}

// --- подготовка поисковой строки ---
people.forEach(person => {
  person._searchStr = [
    person.name,
    person.pos,
    person.org,
    person.address,
    Array.isArray(person.phones) ? person.phones.join(' ') : person.phone || '',
    person.email,
    person.fax
  ].filter(Boolean).join(' ').toLowerCase();
});

// --- поиск ---
function matches(q, person){
  if(!q) return true;
  return person._searchStr.includes(q.toLowerCase());
}

// --- рендер списка ---
function renderGrouped(list){
  const listEl = document.getElementById('resultsList');
  listEl.innerHTML = '';
  const groups = {};
  list.forEach(p => { if(!groups[p.org]) groups[p.org]=[]; groups[p.org].push(p); });
  const orgNames = Object.keys(groups).sort((a,b)=> a.localeCompare(b,'ru'));
  let total = 0;
  orgNames.forEach(orgName=>{
    const block = document.createElement('div'); block.className='org-block';
    const header = document.createElement('div'); header.className='org-header';
    header.innerHTML = `<div><div class="org-name">${orgName}</div></div><div style="font-size:13px;color:var(--muted)">${groups[orgName].length} чел.</div>`;
    block.appendChild(header);
    const pplWrap = document.createElement('div'); pplWrap.className='people';
    groups[orgName].forEach(person=>{
      total++;
      const card = document.createElement('div'); card.className='card'; card.tabIndex=0; card.dataset.id=person.id;
      const pval = firstPhoneValue(person);
      const phoneDisplay = Array.isArray(person.phones) ? person.phones.join(', ') : (person.phone || '—');
      const email = person.email || '—'; 
      const fax = person.fax || '—';
      card.innerHTML = `
        <div class="card-left">
          <div class="avatar">${(person.name||'').charAt(0)}</div>
          <div class="main-info">
            <div class="fio" title="${person.name}">${person.name}</div>
            <div class="pos">${person.pos}</div>
            <div class="orgline">${person.org || ''}${person.address ? ' — ' + person.address : ''}</div>
          </div>
        </div>
        <div class="card-mid">
          <div class="contact-row"><div class="label">Телефон</div><div class="value">${phoneDisplay}</div></div>
          <div class="contact-row"><div class="label">E-mail</div><div class="value">${email}</div></div>
          <div class="contact-row"><div class="label">Факс</div><div class="value">${fax}</div></div>
        </div>
        <div class="card-qr" data-phone="${pval||''}" id="qr-${person.id}"></div>
      `;
      // card.addEventListener('click',()=> showQRModal(person));
      pplWrap.appendChild(card);
    });
    block.appendChild(pplWrap);
    listEl.appendChild(block);
  });
  document.getElementById('countShown').textContent = total;

  // рендер QR
  document.querySelectorAll('.card-qr').forEach(container=>{
    const phone = container.getAttribute('data-phone') || '';
    createQRCodeInContainer(container, phone ? 'tel:' + phone : '', 120);
  });
}

// --- модалка ---
function showQRModal(person){
  const modal = document.getElementById('qrModal');
  const modalQR = document.getElementById('modalQR');
  modalQR.innerHTML = '';

  const phone = firstPhoneValue(person);
  if(phone){
    createQRCodeInContainer(modalQR, 'tel:' + phone, 320);
  } else {
    modalQR.textContent = 'Нет номера';
  }

  modal.style.display = 'flex';
}

// --- инициализация ---
(function init(){
  renderGrouped(people);

  let searchTimeout;
  document.getElementById('search').addEventListener('input', ev => {
    clearTimeout(searchTimeout);
    const q = ev.target.value.trim();
    searchTimeout = setTimeout(()=>{
      renderGrouped(people.filter(p => matches(q,p)));
    },200);
  });

  document.addEventListener('keydown', e=>{
    const cards = Array.from(document.querySelectorAll('.card'));
    if(!cards.length) return;
    const idx = cards.findIndex(c => c.classList.contains('active'));
    if(e.key==='ArrowDown'){ e.preventDefault(); const next=(idx===-1?0:Math.min(cards.length-1, idx+1)); cards[next].focus(); }
    if(e.key==='ArrowUp'){ e.preventDefault(); const prev=(idx===-1?0:Math.max(0, idx-1)); cards[prev].focus(); }
  });

  // закрытие модалки
  document.getElementById('modalClose').onclick = () => document.getElementById('qrModal').style.display='none';
  document.getElementById('qrModal').onclick = e => { if(e.target.id==='qrModal') e.currentTarget.style.display='none'; };

})();

// --- обновление времени ---
setInterval(()=>{ 
  const d=new Date(); 
  document.getElementById('time').textContent = String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0'); 
},1000);
// === Админ-действия в карточках (добавление кнопок редактирования/удаления) ===
(function () {
  if (!window.isLoggedIn) return;

  // Хелпер: создать кнопку
  function makeActionButton(text, href) {
    const a = document.createElement('a');
    a.className = 'btn-action';
    a.textContent = text;
    a.href = href;
    a.addEventListener('click', (e) => {
      // не мешаем клавиатурной навигации, просто ссылка
    });
    return a;
  }

  // Хелпер: форма удаления с CSRF
  function makeDeleteForm(id) {
    const form = document.createElement('form');
    form.method = 'post';
    form.action = 'delete.php';
    form.className = 'inline-delete-form';

    const inId = document.createElement('input');
    inId.type = 'hidden';
    inId.name = 'id';
    inId.value = String(id);

    const inCsrf = document.createElement('input');
    inCsrf.type = 'hidden';
    inCsrf.name = 'csrf';
    inCsrf.value = window.csrf || '';

    const btn = document.createElement('button');
    btn.type = 'submit';
    btn.className = 'btn-action danger';
    btn.textContent = 'Удалить';
    btn.onclick = function () {
      return confirm('Удалить запись?');
    };

    form.appendChild(inId);
    form.appendChild(inCsrf);
    form.appendChild(btn);
    return form;
  }

  // Вставить панель действий в карточки
  function injectActions() {
    const cards = document.querySelectorAll('.list .card');
    cards.forEach(card => {
      if (card.querySelector('.card-actions')) return; // уже добавлено
      const id = card.dataset.id;
      if (!id) return;

      const actions = document.createElement('div');
      actions.className = 'card-actions';

      const edit = makeActionButton('Изменить', 'edit.php?id=' + encodeURIComponent(id));
      const del = makeDeleteForm(id);

      actions.appendChild(edit);
      actions.appendChild(del);

      // Всегда ставим панель действий в конец карточки,
      // чтобы она занимала всю ширину и не вылезала за пределы
      card.appendChild(actions);
    });
  }

  // Первичная попытка
  injectActions();

  // На случай динамических обновлений списка
  const obs = new MutationObserver(() => injectActions());
  obs.observe(document.getElementById('resultsList'), { childList: true, subtree: true });
})();
