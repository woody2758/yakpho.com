  </div><!-- /.page-main -->
</div><!-- /.page-root -->

<!-- Tabler Core + ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
window.confirmForm = function(e, msg) {
    if (e.target.dataset.confirmed) return true;
    e.preventDefault();
    let form = e.target;
    Swal.fire({
        title: 'ยืนยัน?', text: msg, icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
        confirmButtonText: 'ตกลง', cancelButtonText: 'ยกเลิก'
    }).then((r) => { if (r.isConfirmed) { form.dataset.confirmed = true; form.submit(); } });
}
window.confirmLink = function(e, msg) {
    e.preventDefault();
    let url = e.currentTarget.href;
    Swal.fire({
        title: 'ยืนยัน?', text: msg, icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
        confirmButtonText: 'ตกลง', cancelButtonText: 'ยกเลิก'
    }).then((r) => { if (r.isConfirmed) window.location.href = url; });
}

const SB_KEY = 'esthersb';
function toggleSidebar() {
  const c = document.body.classList.toggle('sb-collapsed');
  localStorage.setItem(SB_KEY, c ? '1' : '0');
}
function openSidebar()  { document.body.classList.add('sb-open'); }
function closeSidebar() { document.body.classList.remove('sb-open'); }

// Restore collapsed state on desktop
(function(){
  if (window.innerWidth >= 992 && localStorage.getItem(SB_KEY) === '1') {
    document.body.classList.add('sb-collapsed');
  }
})();

// Persistent Bot Monitoring (Global Polling)
function updateGlobalBotStatus() {
    fetch('api/get_bot_status.php?api_key=yakpho2026')
    .then(r => r.json())
    .then(data => {
        const actionText = data.bot_current_action || 'Waiting...';
        const progress = data.bot_progress_percent || 0;
        const isBotOn = data.bot_status === 'on';

        // 1. Update Spotify-Style Player Bar (Global)
        const playerBar = document.getElementById('bot-player');
        const sidebar = document.getElementById('sidebar');
        const playerAction = document.getElementById('player-bot-action');
        const playerProgress = document.getElementById('player-progress-bar');
        const playerPercent = document.getElementById('player-bot-percent');
        const playerLabel = document.getElementById('player-status-label');
        const playerIcon = document.getElementById('player-status-icon');
        const playerPulse = document.getElementById('player-pulse-indicator');

        if (playerBar) {
            if (isBotOn) {
                playerBar.style.display = 'flex';
                document.body.style.paddingBottom = '72px';
                if (sidebar) sidebar.style.height = 'calc(100vh - 72px)';
            } else {
                playerBar.style.display = 'none';
                document.body.style.paddingBottom = '0';
                if (sidebar) sidebar.style.height = '100vh';
            }
        }

        if (playerAction) playerAction.innerText = actionText;
        if (playerPercent) playerPercent.innerText = progress + '%';
        if (playerProgress) {
            playerProgress.style.width = progress + '%';
            if (progress > 0) playerProgress.classList.remove('progress-bar-indeterminate');
            else playerProgress.classList.add('progress-bar-indeterminate');
        }
        if (playerLabel) {
            playerLabel.innerText = isBotOn ? 'BOT ONLINE' : 'BOT OFFLINE';
            playerLabel.className = isBotOn ? 'badge bg-success-lt fw-bold' : 'badge bg-secondary-lt fw-bold';
        }
        if (playerIcon) {
            playerIcon.className = isBotOn ? 'player-icon pulse-glow' : 'player-icon';
        }
        if (playerPulse) {
            playerPulse.innerText = isBotOn ? 'Pulse Active' : 'Offline';
            playerPulse.className = isBotOn ? 'badge badge-outline text-success' : 'badge badge-outline text-muted';
        }

        // 2. Also update Dashboard Card if we are on index.php
        const dashAction = document.getElementById('bot-current-action');
        const dashProgress = document.getElementById('bot-progress-bar');
        const dashPercent = document.getElementById('bot-progress-percent');
        
        if (dashAction) dashAction.innerText = actionText;
        if (dashProgress) dashProgress.style.width = progress + '%';
        if (dashPercent) dashPercent.innerText = progress + '%';
    })
    .catch(err => console.error('Bot pulse error:', err));
}
// Start Polling every 4 seconds
setInterval(updateGlobalBotStatus, 4000);
updateGlobalBotStatus();
</script>
<!-- Global Tags Modal -->
<div class="modal modal-blur fade" id="modal-manage-tags" tabindex="-1" role="dialog" aria-hidden="true" data-bs-theme="dark">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">จัดการแท็กกลุ่มลูกค้า</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="manage-tag-group-id">
        <label class="form-label fw-bold">จัดการแท็กของสมาชิก:</label>
        
        <div class="border rounded p-2 mb-2 d-flex flex-wrap align-items-center gap-1" id="current-tags-container" style="min-height: 80px; cursor: text;" onclick="document.getElementById('manage-tag-input').focus()">
           <!-- Badges will be injected here -->
           <input type="text" id="manage-tag-input" class="form-control form-control-flush border-0 shadow-none flex-grow-1" placeholder="พิมพ์ชื่อแท็กใหม่แล้วกด Enter..." style="min-width: 200px; background: transparent; color: inherit;">
        </div>
        <div class="small text-muted mb-4" style="font-size: 0.75rem;">พิมพ์ชื่อแท็กแล้วกด Enter เพื่อเพิ่มทันที | กดที่เครื่องหมาย × เพื่อลบออก</div>

        <label class="form-label fw-bold">แท็กแนะนำที่มีในระบบ:</label>
        <div class="d-flex flex-wrap gap-2" id="recommended-tags-container">
           <!-- Buttons will be injected here -->
        </div>
      </div>
      <div class="modal-footer justify-content-center border-0">
        <button type="button" class="btn btn-primary px-4 fw-bold" style="border-radius: 8px;" data-bs-dismiss="modal">เสร็จสิ้น</button>
        <button type="button" class="btn btn-secondary px-4 fw-bold" style="border-radius: 8px;" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

<script>
let recommendedTagsLoaded = false;
let currentEditingTags = [];

function openTagModal(groupId, tagsString) {
    document.getElementById('manage-tag-group-id').value = groupId;
    currentEditingTags = tagsString ? tagsString.split(',').map(t => t.trim()).filter(t => t) : [];
    renderCurrentTags();
    
    if(!recommendedTagsLoaded) {
        fetch('api/get_recommended_tags.php')
            .then(r => r.json())
            .then(res => {
                if(res.ok) {
                    const container = document.getElementById('recommended-tags-container');
                    container.innerHTML = '';
                    res.tags.forEach(tag => {
                        const btn = document.createElement('button');
                        btn.className = 'btn btn-sm rounded-pill btn-outline-secondary';
                        btn.innerText = tag;
                        btn.onclick = () => addTag(tag);
                        container.appendChild(btn);
                    });
                    recommendedTagsLoaded = true;
                }
            });
    }
    
    new bootstrap.Modal(document.getElementById('modal-manage-tags')).show();
}

function renderCurrentTags() {
    const container = document.getElementById('current-tags-container');
    // Remove all badges
    container.querySelectorAll('.tag-badge').forEach(e => e.remove());
    
    const input = document.getElementById('manage-tag-input');
    
    currentEditingTags.forEach(tag => {
        const badge = document.createElement('span');
        badge.className = 'badge bg-secondary text-white tag-badge d-flex align-items-center gap-1 py-1 px-2';
        badge.innerHTML = `<span>${tag}</span> <a href="javascript:void(0)" class="text-white text-decoration-none" onclick="removeTag('${tag}')">&times;</a>`;
        container.insertBefore(badge, input);
    });
}

function addTag(tag) {
    tag = tag.trim();
    if(tag && !currentEditingTags.includes(tag)) {
        currentEditingTags.push(tag);
        renderCurrentTags();
        saveTagsToServer();
    }
    document.getElementById('manage-tag-input').value = '';
}

function removeTag(tag) {
    currentEditingTags = currentEditingTags.filter(t => t !== tag);
    renderCurrentTags();
    saveTagsToServer();
}

document.getElementById('manage-tag-input').addEventListener('keypress', function(e) {
    if(e.key === 'Enter') {
        e.preventDefault();
        addTag(this.value);
    }
});

function saveTagsToServer() {
    const groupId = document.getElementById('manage-tag-group-id').value;
    const tagStr = currentEditingTags.join(',');
    
    // Update server
    fetch('api/update_group_info.php?api_key=yakpho2026', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: groupId, field: 'campaign_tag', value: tagStr })
    }).then(r => r.json()).then(res => {
        if(res.ok) {
            // Update UI in table
            const cell = document.getElementById('tags-cell-' + groupId);
            if(cell) {
                let html = `<div class="d-flex flex-wrap align-items-center gap-1">`;
                html += `<span class="badge bg-secondary-lt text-muted" style="cursor: pointer; padding: 2px 4px; font-size: 10px; font-weight: normal;" onclick="openTagModal(${groupId}, '${tagStr.replace(/'/g, "\\'")}')" title="เพิ่ม/แก้ไขแท็ก">+</span>`;
                if(currentEditingTags.length > 0) {
                    html += currentEditingTags.map(t => `<div class="badge bg-secondary-lt" style="font-size: 9px; font-weight: normal; padding: 2px 6px;">${t}</div>`).join('');
                }
                html += `</div>`;
                cell.innerHTML = html;
            }
        }
    });
}
</script>

</body>
</html>
