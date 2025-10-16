// ============================================
// MANAGE ADS JAVASCRIPT
// ============================================

// ============================================
// OPEN ADD MODAL
// ============================================
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Advertisement';
    document.getElementById('adForm').reset();
    document.getElementById('ad_id').value = '';
    document.getElementById('adModal').style.display = 'block';
}

// ============================================
// EDIT AD
// ============================================
function editAd(ad) {
    document.getElementById('modalTitle').textContent = 'Edit Advertisement';
    document.getElementById('ad_id').value = ad.id;
    document.getElementById('title').value = ad.title;
    document.getElementById('description').value = ad.description;
    document.getElementById('video_url').value = ad.video_url;
    document.getElementById('image_url').value = ad.image_url || '';
    document.getElementById('url').value = ad.url;
    document.getElementById('reward').value = ad.reward;
    document.getElementById('duration').value = ad.duration;
    document.getElementById('minimum_watch_time').value = ad.minimum_watch_time;
    document.getElementById('is_active').value = ad.is_active ? '1' : '0';
    
    document.getElementById('adModal').style.display = 'block';
}

// ============================================
// CLOSE MODAL
// ============================================
function closeModal() {
    document.getElementById('adModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('adModal');
    if (event.target == modal) {
        closeModal();
    }
}

// ============================================
// SUBMIT FORM
// ============================================
document.getElementById('adForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('.btn-submit');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    try {
        const response = await fetch('save_ad.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            closeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Advertisement';
        }
    } catch (error) {
        showNotification('An error occurred. Please try again.', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save Advertisement';
    }
});

// ============================================
// TOGGLE AD STATUS
// ============================================
async function toggleAdStatus(adId, newStatus) {
    const action = newStatus === 'true' ? 'activate' : 'deactivate';
    
    if (!confirm(`Are you sure you want to ${action} this advertisement?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('ad_id', adId);
    formData.append('is_active', newStatus);
    
    try {
        const response = await fetch('toggle_ad_status.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// ============================================
// DELETE AD
// ============================================
async function deleteAd(adId, adTitle) {
    if (!confirm(`Are you sure you want to delete "${adTitle}"? This action cannot be undone.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('ad_id', adId);
    
    try {
        const response = await fetch('delete_ad.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// ============================================
// SEARCH FUNCTIONALITY
// ============================================
document.getElementById('searchAds').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.data-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// ============================================
// SHOW NOTIFICATION
// ============================================
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db'};
        color: white;
        padding: 1rem 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideIn 0.3s ease;
        font-weight: 500;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ============================================
// ANIMATION STYLES
// ============================================
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);