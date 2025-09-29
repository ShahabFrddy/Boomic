// script.js - نسخه اصلاح شده

console.log('Script.js loaded successfully!'); // برای تست لود شدن

// متغیرهای global
let contextMenu = null;
let currentContextServerId = null;
let currentProfileModal = null;

// توابع مودال
function openModal(modalId) {
    console.log('Opening modal:', modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    } else {
        console.error('Modal not found:', modalId);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// منوی کشویی برای سرورها
function createContextMenu() {
    if (contextMenu) {
        contextMenu.remove();
    }
    
    contextMenu = document.createElement('div');
    contextMenu.className = 'context-menu';
    contextMenu.innerHTML = `
        <div class="context-menu-item" onclick="showServerMembersFromContext()">
            <span>👥 نمایش اعضا</span>
        </div>
        <div class="context-menu-item" onclick="showInviteModalFromContext()">
            <span>🔗 لینک دعوت</span>
        </div>
    `;
    document.body.appendChild(contextMenu);
}

function showContextMenu(event, serverId) {
    event.preventDefault();
    event.stopPropagation();
    
    currentContextServerId = serverId;
    console.log('Context menu for server:', serverId);
    createContextMenu();
    
    const x = event.clientX;
    const y = event.clientY;
    
    contextMenu.style.left = x + 'px';
    contextMenu.style.top = y + 'px';
    contextMenu.style.display = 'block';
    
    setTimeout(() => {
        document.addEventListener('click', hideContextMenu);
    }, 100);
}

function hideContextMenu() {
    if (contextMenu) {
        contextMenu.style.display = 'none';
    }
    document.removeEventListener('click', hideContextMenu);
}

// نمایش اعضا از منوی کشویی
async function showServerMembersFromContext() {
    console.log('Showing members for server:', currentContextServerId);
    
    if (!currentContextServerId) {
        alert('خطا: سرور مشخص نشده');
        return;
    }
    
    try {
        const response = await fetch(`get_members.php?server_id=${currentContextServerId}`);
        const data = await response.json();
        console.log('Members data:', data);
        
        const membersList = document.getElementById('members-list');
        if (!membersList) {
            console.error('Members list container not found');
            return;
        }
        
        membersList.innerHTML = '';
        
        if (data.error) {
            membersList.innerHTML = `<p style="color: #ed4245; text-align: center;">${data.error}</p>`;
            return;
        }
        
        if (data.members && data.members.length > 0) {
            data.members.forEach(member => {
                const memberElement = `
                    <div class="member-item" onclick="showUserProfile(${member.id})">
                        <img class="friend-avatar" src="uploads/${member.avatar}" alt="${member.username}"
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxNiIgcj0iMTYiIGZpbGw9IiM1ODY1RjIiLz4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxMiIgcj0iNiIgZmlsbD0iI2RjZGRkZSIvPgo8cGF0aCBkPSJNMTYgMjBDMjAgMjAgMjQgMjIgMjQgMjZIMThDMTggMjIgMTYgMjAgMTYgMjBaIiBmaWxsPSIjZGNkZGRlIi8+Cjwvc3ZnPgo='">
                        <div style="flex-grow: 1;">
                            <div style="color: white; font-weight: 500;">
                                ${member.username}
                                ${member.is_owner ? '<span style="color: #faa81a; font-size: 12px;"> (مالک)</span>' : ''}
                            </div>
                            <div style="color: #b9bbbe; font-size: 12px;">آنلاین</div>
                        </div>
                    </div>
                `;
                membersList.innerHTML += memberElement;
            });
        } else {
            membersList.innerHTML = '<p style="color: #b9bbbe; text-align: center;">هیچ عضوی یافت نشد</p>';
        }
    } catch (error) {
        console.error('Error loading members:', error);
        const membersList = document.getElementById('members-list');
        if (membersList) {
            membersList.innerHTML = '<p style="color: #ed4245; text-align: center;">خطا در بارگذاری اعضا</p>';
        }
    }
    
    hideContextMenu();
    openModal('membersModal');
}

// نمایش مدال لینک دعوت
async function showInviteModalFromContext() {
    console.log('Showing invites for server:', currentContextServerId);
    
    if (!currentContextServerId) {
        alert('خطا: سرور مشخص نشده');
        return;
    }
    
    try {
        const response = await fetch(`check_server_owner.php?server_id=${currentContextServerId}`);
        const data = await response.json();
        console.log('Owner check data:', data);
        
        if (!data.is_owner) {
            alert('فقط مالک سرور می‌تواند لینک دعوت ایجاد کند');
            return;
        }
        
        window.currentInviteServerId = currentContextServerId;
        await loadActiveInvites();
        hideContextMenu();
        openModal('inviteModal');
    } catch (error) {
        console.error('Error checking server owner:', error);
        alert('خطا در بررسی دسترسی');
    }
}

// بارگذاری لینک‌های دعوت
async function loadActiveInvites() {
    const serverId = window.currentInviteServerId || currentContextServerId;
    
    if (!serverId) {
        console.error('No server ID available');
        return;
    }
    
    try {
        const response = await fetch(`get_invites.php?server_id=${serverId}`);
        const data = await response.json();
        console.log('Invites data:', data);
        
        const invitesContainer = document.getElementById('active-invites');
        if (!invitesContainer) {
            console.error('Invites container not found');
            return;
        }
        
        invitesContainer.innerHTML = '';
        
        if (data.invites && data.invites.length > 0) {
            data.invites.forEach(invite => {
                const inviteElement = `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #2f3136; border-radius: 4px; margin-bottom: 5px;">
                        <div>
                            <div style="color: white; font-size: 14px;">${invite.code}</div>
                            <div style="color: #b9bbbe; font-size: 12px;">
                                ایجاد شده توسط ${invite.created_by_username}
                                ${invite.uses_count > 0 ? ` • ${invite.uses_count} استفاده` : ''}
                                ${invite.expires_at ? ` • منقضی: ${new Date(invite.expires_at).toLocaleDateString('fa-IR')}` : ''}
                            </div>
                        </div>
                        <button class="btn" style="padding: 4px 8px; font-size: 12px;" 
                                onclick="copyInviteLink('${invite.code}')">
                            کپی
                        </button>
                    </div>
                `;
                invitesContainer.innerHTML += inviteElement;
            });
        } else {
            invitesContainer.innerHTML = '<p style="color: #b9bbbe; text-align: center;">هیچ لینک دعوتی وجود ندارد</p>';
        }
    } catch (error) {
        console.error('Error loading invites:', error);
        const invitesContainer = document.getElementById('active-invites');
        if (invitesContainer) {
            invitesContainer.innerHTML = '<p style="color: #ed4245; text-align: center;">خطا در بارگذاری لینک‌ها</p>';
        }
    }
}

// ایجاد لینک دعوت
async function generateInvite() {
    const serverId = window.currentInviteServerId || currentContextServerId;
    
    if (!serverId) {
        alert('خطا: سرور مشخص نشده');
        return;
    }
    
    try {
        const response = await fetch('create_invite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `server_id=${serverId}`
        });
        const data = await response.json();
        console.log('Generate invite response:', data);
        
        if (data.success) {
            const inviteLink = `${window.location.origin}/join.php?code=${data.code}`;
            document.getElementById('new-invite-link').value = inviteLink;
            await loadActiveInvites();
        } else {
            alert(data.error || 'خطا در ایجاد لینک دعوت');
        }
    } catch (error) {
        console.error('Error generating invite:', error);
        alert('خطا در ایجاد لینک دعوت');
    }
}

// کپی لینک دعوت
function copyInviteLink(code) {
    const inviteLink = `${window.location.origin}/join.php?code=${code}`;
    navigator.clipboard.writeText(inviteLink).then(() => {
        alert('لینک دعوت کپی شد');
    }).catch(() => {
        const tempInput = document.createElement('input');
        tempInput.value = inviteLink;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        alert('لینک دعوت کپی شد');
    });
}

// نمایش پروفایل کاربر
async function showUserProfile(userId) {
    console.log('Showing profile for user:', userId);
    
    try {
        const response = await fetch(`get_user_profile.php?user_id=${userId}`);
        const data = await response.json();
        console.log('Profile data:', data);
        
        if (data.error) {
            alert('خطا: ' + data.error);
            return;
        }

        // بستن مودال قبلی اگر باز باشد
        if (currentProfileModal) {
            currentProfileModal.remove();
        }

        // ایجاد مودال پروفایل کاربر
        const profileModal = document.createElement('div');
        profileModal.className = 'modal user-profile-modal';
        profileModal.innerHTML = `
            <div class="modal-content">
                <div class="user-profile-header">
                    <img class="user-profile-avatar" src="uploads/${data.avatar}" alt="${data.username}"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCA4MCA4MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iNDAiIGN5PSI0MCIgcj0iNDAiIGZpbGw9IiM1ODY1RjIiLz4KPGNpcmNsZSBjeD0iNDAiIGN5PSIzMCIgcj0iMTUiIGZpbGw9IiNkY2RkZGUiLz4KPHBhdGggZD0iTTQwIDUwQzUwIDUwIDU4IDU4IDU4IDY4SDIyQzIyIDU4IDMwIDUwIDQwIDUwWiIgZmlsbD0iI2RjZGRkZSIvPgo8L3N2Zz4K'">
                    <div class="user-profile-name">${data.username}</div>
                    <div class="user-profile-info">عضو شده در: ${data.join_date}</div>
                </div>
                <div class="user-profile-body">
                    ${data.bio && data.bio.trim() !== '' ? `
                    <div class="user-profile-bio">
                        <h4>درباره</h4>
                        <p>${data.bio}</p>
                    </div>
                    ` : `
                    <div class="user-profile-bio">
                        <h4>درباره</h4>
                        <p style="color: #72767d; font-style: italic;">این کاربر هنوز بیوگرافی اضافه نکرده است</p>
                    </div>
                    `}
                    
                    <div class="user-profile-actions">
                        ${getFriendButton(data)}
                        <button class="btn btn-message" onclick="startDirectMessage(${data.id})">
                            ✉️ ارسال پیام
                        </button>
                    </div>
                    
                    ${data.error ? `
                    <div style="color: #ed4245; margin-top: 10px; text-align: center; font-size: 14px;">
                        ${data.error}
                    </div>
                    ` : ''}
                </div>
            </div>
        `;

        document.body.appendChild(profileModal);
        profileModal.style.display = 'flex';
        currentProfileModal = profileModal;

        // بستن مودال با کلیک خارج
        profileModal.addEventListener('click', function(e) {
            if (e.target === profileModal) {
                closeUserProfile();
            }
        });
    } catch (error) {
        console.error('Error loading user profile:', error);
        alert('خطا در بارگذاری پروفایل کاربر');
    }
}

// تابع کمکی برای ایجاد دکمه دوستی
function getFriendButton(userData) {
    if (userData.is_friend) {
        return `<button class="btn btn-friend" disabled>✓ دوست</button>`;
    } else if (userData.has_pending_request) {
        return `<button class="btn btn-pending" disabled>⏳ درخواست ارسال شده</button>`;
    } else {
        return `<button class="btn btn-friend" onclick="sendFriendRequest(${userData.id})">👥 افزودن دوست</button>`;
    }
}

// بستن مودال پروفایل کاربر
function closeUserProfile() {
    if (currentProfileModal) {
        currentProfileModal.remove();
        currentProfileModal = null;
    }
}

// ارسال درخواست دوستی
async function sendFriendRequest(userId) {
    try {
        const response = await fetch('send_friend_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `target_user_id=${userId}`
        });
        const data = await response.json();
        
        if (data.success) {
            alert('درخواست دوستی ارسال شد');
            closeUserProfile();
        } else {
            alert(data.error || 'خطا در ارسال درخواست');
        }
    } catch (error) {
        console.error('Error sending friend request:', error);
        alert('خطا در ارسال درخواست');
    }
}

// شروع چت خصوصی
function startDirectMessage(userId) {
    closeUserProfile();
    window.location.href = `dm.php?friend_id=${userId}`;
}

// جلوگیری از منوی پیش‌فرض مرورگر
document.addEventListener('contextmenu', function(e) {
    if (e.target.closest('.server-icon')) {
        e.preventDefault();
    }
});

// بستن مودال با کلیک خارج
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});

// بستن مودال با کلید ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
        if (currentProfileModal) {
            currentProfileModal.remove();
            currentProfileModal = null;
        }
    }
});

// مقداردهی اولیه
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded');
    
    // اسکرول به پایین در پیام‌ها
    const messagesContainer = document.getElementById('messages-container');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
});

