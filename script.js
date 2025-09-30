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
        <div class="context-menu-item" onclick="showServerSettings()">
            <span>⚙️ تنظیمات سرور</span>
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
                    
                    <div class="user-profile-name">
                        ${data.username}
                        ${data.verified == 1 ? `
                            <span class="verified-badge" title="تایید شده">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                            </span>
                        ` : ''}
                    </div>
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

// نمایش تنظیمات سرور
// نمایش تنظیمات سرور - نسخه به‌روز شده با دکمه حذف
async function showServerSettings() {
    if (!currentContextServerId) {
        alert('خطا: سرور مشخص نشده');
        return;
    }
    
    try {
        const response = await fetch(`get_server_info.php?server_id=${currentContextServerId}`);
        const data = await response.json();
        
        if (data.error) {
            alert('خطا: ' + data.error);
            return;
        }

        // ایجاد مودال تنظیمات سرور
        const settingsModal = document.createElement('div');
        settingsModal.className = 'modal server-settings-modal';
        settingsModal.innerHTML = `
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3>تنظیمات سرور</h3>
                    <button type="button" class="back-button" onclick="closeServerSettings()">×</button>
                </div>
                <div class="modal-body">
                    <form id="server-settings-form" enctype="multipart/form-data">
                        <input type="hidden" name="server_id" value="${data.server.id}">
                        
                        <div class="form-group">
                            <label>آیکون سرور</label>
                            <div style="text-align: center; margin-bottom: 20px;">
                                <img id="server-icon-preview" src="uploads/${data.server.icon}" 
                                     style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; cursor: pointer;"
                                     onclick="document.getElementById('server-icon-input').click()"
                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCA4MCA4MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iNDAiIGN5PSI0MCIgcj0iNDAiIGZpbGw9IiM1ODY1RjIiLz4KPGNpcmNsZSBjeD0iNDAiIGN5PSIzMCIgcj0iMTUiIGZpbGw9IiNkY2RkZGUiLz4KPHBhdGggZD0iTTQwIDUwQzUwIDUwIDU4IDU4IDU4IDY4SDIyQzIyIDU4IDMwIDUwIDQwIDUwWiIgZmlsbD0iI2RjZGRkZSIvPgo8L3N2Zz4K'">
                                <input type="file" id="server-icon-input" name="server_icon" accept="image/*" style="display: none;" onchange="previewServerIcon(this)">
                                <div style="color: #b9bbbe; font-size: 12px; margin-top: 5px;">برای تغییر آیکون کلیک کنید</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="server-name">نام سرور</label>
                            <input type="text" class="form-control" id="server-name" name="server_name" 
                                   value="${data.server.name}" required>
                        </div>
                        
                        <div class="form-group">
                            <label>اطلاعات سرور</label>
                            <div style="background: #2f3136; padding: 15px; border-radius: 4px;">
                                <div style="color: #b9bbbe; font-size: 12px;">
                                    <div>مالک: شما</div>
                                    <div>تاریخ ایجاد: ${new Date(data.server.created_at).toLocaleDateString('fa-IR')}</div>
                                    <div>تعداد اعضا: ${data.server.member_count || 1} نفر</div>
                                    <div>تعداد کانال‌ها: ${data.server.channel_count || 0} کانال</div>
                                    <div>ID سرور: ${data.server.id}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="button" class="btn btn-secondary" onclick="closeServerSettings()" style="flex: 1;">
                                لغو
                            </button>
                            <button type="submit" class="btn" style="flex: 1;">
                                ذخیره تغییرات
                            </button>
                        </div>
                    </form>

                    <!-- بخش حذف سرور -->
                    <div class="danger-zone">
                        <h4 style="color: #ed4245; margin-bottom: 15px;">⚠️ منطقه خطر</h4>
                        <p style="color: #b9bbbe; font-size: 14px; margin-bottom: 15px;">
                            با حذف سرور، تمام اطلاعات شامل کانال‌ها، پیام‌ها و اعضا به طور دائمی پاک می‌شوند. این عمل غیرقابل بازگشت است.
                        </p>
                        <button type="button" class="btn btn-danger" onclick="showDeleteConfirmation(${data.server.id}, '${data.server.name}')">
                            🗑️ حذف سرور
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(settingsModal);
        settingsModal.style.display = 'flex';
        window.currentSettingsModal = settingsModal;

        // مدیریت ارسال فرم
        const form = document.getElementById('server-settings-form');
        form.addEventListener('submit', handleServerSettingsSubmit);

        // بستن مودال با کلیک خارج
        settingsModal.addEventListener('click', function(e) {
            if (e.target === settingsModal) {
                closeServerSettings();
            }
        });

    } catch (error) {
        console.error('Error loading server settings:', error);
        alert('خطا در بارگذاری تنظیمات سرور');
    }
    
    hideContextMenu();
}
// نمایش تایید حذف سرور
function showDeleteConfirmation(serverId, serverName) {
    const confirmationModal = document.createElement('div');
    confirmationModal.className = 'modal delete-confirmation-modal';
    confirmationModal.innerHTML = `
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h3 style="color: #ed4245;">⚠️ حذف سرور</h3>
                <button type="button" class="back-button" onclick="closeDeleteConfirmation()">×</button>
            </div>
            <div class="modal-body">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="font-size: 48px; color: #ed4245; margin-bottom: 10px;">🗑️</div>
                    <h4 style="color: white; margin-bottom: 10px;">آیا مطمئن هستید؟</h4>
                </div>
                
                <div style="background: #2f3136; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <p style="color: #dcddde; margin-bottom: 10px;">
                        شما در حال حذف سرور <strong>"${serverName}"</strong> هستید.
                    </p>
                    <p style="color: #ed4245; font-size: 14px;">
                        این عمل تمام اطلاعات زیر را به طور دائمی پاک می‌کند:
                    </p>
                    <ul style="color: #b9bbbe; font-size: 14px; margin: 10px 0; padding-right: 20px;">
                        <li>همه کانال‌ها و پیام‌ها</li>
                        <li>لیست اعضا و عضویت‌ها</li>
                        <li>لینک‌های دعوت</li>
                        <li>تنظیمات سرور</li>
                    </ul>
                    <p style="color: #ed4245; font-size: 14px; font-weight: bold;">
                        این عمل غیرقابل بازگشت است!
                    </p>
                </div>

                <div class="form-group">
                    <label for="delete-confirmation-input">
                        برای تایید، عبارت <strong>"delete"</strong> را در کادر زیر تایپ کنید:
                    </label>
                    <input type="text" class="form-control" id="delete-confirmation-input" 
                           placeholder="delete" style="text-align: center;">
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteConfirmation()" style="flex: 1;">
                        لغو
                    </button>
                    <button type="button" class="btn btn-danger" id="delete-server-btn" disabled style="flex: 1;">
                        حذف سرور
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(confirmationModal);
    confirmationModal.style.display = 'flex';
    window.currentDeleteModal = confirmationModal;

    // مدیریت ورودی تایید
    const confirmationInput = document.getElementById('delete-confirmation-input');
    const deleteButton = document.getElementById('delete-server-btn');

    confirmationInput.addEventListener('input', function() {
        deleteButton.disabled = this.value.toLowerCase() !== 'delete';
    });

    // مدیریت کلیک دکمه حذف
    deleteButton.addEventListener('click', function() {
        deleteServer(serverId);
    });

    // بستن مودال با کلیک خارج
    confirmationModal.addEventListener('click', function(e) {
        if (e.target === confirmationModal) {
            closeDeleteConfirmation();
        }
    });

    // بستن مودال تنظیمات اصلی
    closeServerSettings();
}

// بستن مودال تایید حذف
function closeDeleteConfirmation() {
    if (window.currentDeleteModal) {
        window.currentDeleteModal.remove();
        window.currentDeleteModal = null;
    }
}

// حذف سرور
async function deleteServer(serverId) {
    const confirmationInput = document.getElementById('delete-confirmation-input');
    
    if (confirmationInput.value.toLowerCase() !== 'delete') {
        alert('لطفاً عبارت "delete" را برای تایید وارد کنید');
        return;
    }

    try {
        const response = await fetch('delete_server.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `server_id=${serverId}&confirmation=delete`
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            closeDeleteConfirmation();
            
            // ریدایرکت به صفحه اصلی
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.href = 'index.php';
            }
        } else {
            alert(data.error || 'خطا در حذف سرور');
        }
    } catch (error) {
        console.error('Error deleting server:', error);
        alert('خطا در حذف سرور');
    }
}

// پیش‌نمایش آیکون سرور
function previewServerIcon(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('server-icon-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// مدیریت ارسال فرم تنظیمات
async function handleServerSettingsSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('update_server_settings.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            closeServerSettings();
            // رفرش صفحه برای نمایش تغییرات
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert(data.error || 'خطا در ذخیره تنظیمات');
        }
    } catch (error) {
        console.error('Error updating server settings:', error);
        alert('خطا در ذخیره تنظیمات');
    }
}

// بستن مودال تنظیمات
function closeServerSettings() {
    if (window.currentSettingsModal) {
        window.currentSettingsModal.remove();
        window.currentSettingsModal = null;
    }
}

// نمایش تایید خروج
function showLogoutConfirmation() {
    const logoutModal = document.createElement('div');
    logoutModal.className = 'modal logout-confirmation-modal';
    logoutModal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>خروج از حساب کاربری</h3>
                <button type="button" class="back-button" onclick="closeLogoutConfirmation()">×</button>
            </div>
            <div class="modal-body">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="font-size: 48px; color: #5865f2; margin-bottom: 10px;">🚪</div>
                    <h4 style="color: white; margin-bottom: 10px;">آیا می‌خواهید خارج شوید؟</h4>
                    <p style="color: #b9bbbe; font-size: 14px;">
                        پس از خروج، برای دسترسی دوباره باید وارد حساب کاربری خود شوید.
                    </p>
                </div>
                
                <div class="logout-options">
                    <button type="button" class="btn btn-cancel" onclick="closeLogoutConfirmation()">
                        انصراف
                    </button>
                    <button type="button" class="btn btn-logout" onclick="logout()">
                        بله، خارج شوم
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(logoutModal);
    logoutModal.style.display = 'flex';
    window.currentLogoutModal = logoutModal;

    // بستن مودال با کلیک خارج
    logoutModal.addEventListener('click', function(e) {
        if (e.target === logoutModal) {
            closeLogoutConfirmation();
        }
    });

    // بستن مودال با کلید ESC
    document.addEventListener('keydown', function closeOnEscape(e) {
        if (e.key === 'Escape') {
            closeLogoutConfirmation();
            document.removeEventListener('keydown', closeOnEscape);
        }
    });
}

// بستن مودال تایید خروج
function closeLogoutConfirmation() {
    if (window.currentLogoutModal) {
        window.currentLogoutModal.remove();
        window.currentLogoutModal = null;
    }
}

// انجام عملیات خروج
function logout() {
    // نمایش پیام در حال خروج
    if (window.currentLogoutModal) {
        window.currentLogoutModal.querySelector('.modal-body').innerHTML = `
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 48px; color: #5865f2; margin-bottom: 10px;">⏳</div>
                <h4 style="color: white; margin-bottom: 10px;">در حال خروج...</h4>
                <p style="color: #b9bbbe; font-size: 14px;">
                    لطفاً کمی صبر کنید
                </p>
            </div>
        `;
    }

    // ریدایرکت به صفحه خروج
    setTimeout(() => {
        window.location.href = 'logout.php';
    }, 1000);
}

// مدیریت آپلود فایل
document.getElementById('file-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        document.getElementById('file-name').textContent = file.name;
        document.getElementById('file-preview').style.display = 'flex';
        
        // نمایش پیش‌نمایش برای عکس
        if (file.type.startsWith('image/')) {
            showImagePreview(file);
        }
    }
});

function clearFile() {
    document.getElementById('file-input').value = '';
    document.getElementById('file-preview').style.display = 'none';
    hideImagePreview();
}

function showImagePreview(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        // ایجاد پیش‌نمایش تصویر
        let preview = document.getElementById('image-preview');
        if (!preview) {
            preview = document.createElement('div');
            preview.id = 'image-preview';
            preview.style.cssText = `
                position: relative;
                margin: 10px 0;
                max-width: 200px;
                border-radius: 8px;
                overflow: hidden;
            `;
            document.querySelector('.input-wrapper').parentNode.insertBefore(preview, document.querySelector('.input-wrapper'));
        }
        
        preview.innerHTML = `
            <img src="${e.target.result}" style="width: 100%; height: auto; display: block;">
            <button type="button" onclick="hideImagePreview()" style="
                position: absolute;
                top: 5px;
                right: 5px;
                background: #00000080;
                border: none;
                color: white;
                border-radius: 50%;
                width: 24px;
                height: 24px;
                cursor: pointer;
            ">×</button>
        `;
    };
    reader.readAsDataURL(file);
}

function hideImagePreview() {
    const preview = document.getElementById('image-preview');
    if (preview) {
        preview.remove();
    }
}


// مدال برای نمایش رسانه‌ها
function openMediaModal(url, type) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
    `;
    
    let content = '';
    if (type === 'image') {
        content = `<img src="${url}" style="max-width: 90%; max-height: 90%; border-radius: 8px;">`;
    }
    
    modal.innerHTML = `
        <div style="position: relative;">
            ${content}
            <button onclick="this.parentElement.parentElement.remove()" style="
                position: absolute;
                top: -40px;
                right: 0;
                background: none;
                border: none;
                color: white;
                font-size: 30px;
                cursor: pointer;
            ">×</button>
        </div>
    `;
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    document.body.appendChild(modal);
}