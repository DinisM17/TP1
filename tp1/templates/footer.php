<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/path.php';
?>
</main>

<footer style="margin-top:26px; border-top:1px solid rgba(255,255,255,.10); background: rgba(12,18,35,.55);">
  <div class="container" style="display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; align-items:flex-start;">
    <div>
      <div style="font-weight:900;">IAedu Académicos</div>
      <div class="subtle" style="max-width:520px;">
        Aplicação interna para processos académicos — fichas, matrículas e pautas.
      </div>

      <div class="row" style="margin-top:12px;">
        <button
          id="qrBtn"
          type="button"
          class="btn btn-ghost"
          title="Abrir no telemóvel (QR Code)"
          aria-label="Abrir no telemóvel (QR Code)"
          style="padding:10px 12px; line-height:0;"
        >
          <!-- QR icon (SVG) -->
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M3 3h8v8H3V3Zm2 2v4h4V5H5Zm8-2h8v8h-8V3Zm2 2v4h4V5h-4ZM3 13h8v8H3v-8Zm2 2v4h4v-4H5Zm10 0h2v2h-2v-2Zm2 2h2v2h-2v-2Zm-2 2h2v2h-2v-2Zm4-6h2v6h-2v-6Zm-4 0h2v2h-2v-2Zm6 6h-6v-2h6v2Zm0-10h-2v2h2V11Z"
                  fill="currentColor"/>
          </svg>
        </button>
      </div>
    </div>

    <div style="text-align:right;">
      <div class="subtle">Atalhos</div>
      <div class="row" style="justify-content:flex-end; margin-top:8px;">
        <a href="<?= BASE_URL ?>/public/index.php">Portal</a>
        <span class="subtle">·</span>
        <a href="<?= BASE_URL ?>/public/login.php">Entrar</a>
      </div>
      <div class="subtle" style="margin-top:10px; font-size:12px;">
        © <?= date('Y') ?> IAedu Académicos
      </div>
    </div>
  </div>
</footer>

<!-- Modal QR -->
<div id="qrModal" style="
  display:none;
  position:fixed;
  inset:0;
  background: rgba(0,0,0,.55);
  z-index: 9999;
  padding: 18px;
">
  <div class="card" style="max-width:520px; margin: 7vh auto; position:relative;">
    <div class="card-header">
      <div>
        <h2 style="margin:0;">Abrir no telemóvel</h2>
        <div class="subtle">Lê o QR code para abrir este link no smartphone.</div>
      </div>
      <button id="qrClose" type="button" class="btn btn-ghost" style="padding:8px 10px;">Fechar</button>
    </div>

    <div class="row" style="justify-content:center; margin-top:10px;">
      <img id="qrImg" alt="QR Code" style="width:260px; height:260px; border-radius:16px; border:1px solid rgba(255,255,255,.14); background: rgba(255,255,255,.06);">
    </div>

    <div class="card" style="box-shadow:none; margin-top:12px; background: rgba(255,255,255,.03);">
      <div class="subtle" style="margin-bottom:6px;">Link:</div>
      <input id="qrLink" readonly style="cursor:text;">
      <div class="row" style="margin-top:10px;">
        <button id="qrCopy" type="button" class="btn">Copiar link</button>
      </div>
      <div class="subtle" style="margin-top:10px; font-size:12px;">
        Nota: no XAMPP/localhost, o telemóvel só abre se estiveres na mesma rede e usares o IP do PC.
      </div>
    </div>
  </div>
</div>

<script>
(() => {
  const btn = document.getElementById('qrBtn');
  const modal = document.getElementById('qrModal');
  const closeBtn = document.getElementById('qrClose');
  const img = document.getElementById('qrImg');
  const linkInput = document.getElementById('qrLink');
  const copyBtn = document.getElementById('qrCopy');

  const LAN_IP = '172.16.64.55';

  function normalizeForMobile(url){
    try {
      const u = new URL(url);
      if (u.hostname === 'localhost' || u.hostname === '127.0.0.1') {
        u.hostname = LAN_IP;
      }
      return u.toString();
    } catch {
      return url;
    }
  }

  function openModal(){
    const current = window.location.href;
    const mobileUrl = normalizeForMobile(current);

    linkInput.value = mobileUrl;

    const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data='
      + encodeURIComponent(mobileUrl);

    img.src = qrUrl;
    modal.style.display = 'block';
  }

  function closeModal(){
    modal.style.display = 'none';
  }

  btn?.addEventListener('click', openModal);
  closeBtn?.addEventListener('click', closeModal);

  modal?.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.style.display === 'block') closeModal();
  });

  copyBtn?.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(linkInput.value);
      copyBtn.textContent = 'Copiado!';
      setTimeout(() => copyBtn.textContent = 'Copiar link', 900);
    } catch {
      linkInput.focus();
      linkInput.select();
      document.execCommand('copy');
    }
  });
})();
</script>

</body>
</html>