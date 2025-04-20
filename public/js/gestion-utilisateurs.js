// Variables globales
let userIdToDelete = null;

// Fonction pour afficher la modale de suppression
function showDeleteModal(userId, nom, prenom) {
    userIdToDelete = userId;
    const modal = document.getElementById('deleteModal');
    const userName = document.getElementById('userName');
    
    userName.textContent = `${nom} ${prenom}`;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Fonction pour fermer la modale
function closeModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    userIdToDelete = null;
}

// Fonction pour afficher les notifications
function showNotification(message, isSuccess = true) {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification ${isSuccess ? 'success' : 'error'}`;
    notification.classList.add('show');

    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// Fonction pour supprimer un utilisateur
async function deleteUser() {
    if (!userIdToDelete) return;

    try {
        const formData = new FormData();
        formData.append('action', 'supprimer');
        formData.append('user_id', userIdToDelete);

        const response = await fetch('', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('Erreur réseau');
        }

        const data = await response.json();

        if (data.success) {
            showNotification(data.message || 'Utilisateur supprimé avec succès', true);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            throw new Error(data.message || 'Erreur lors de la suppression');
        }
    } catch (error) {
        showNotification(error.message, false);
    } finally {
        closeModal();
    }
}

// Fonction pour changer le rôle d'un utilisateur
async function changerRole(userId, nouveauRole) {
    try {
        const formData = new FormData();
        formData.append('action', 'changerRole');
        formData.append('user_id', userId);
        formData.append('role', nouveauRole);

        const response = await fetch('', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('Erreur réseau');
        }

        const data = await response.json();

        if (data.success) {
            showNotification(data.message || 'Rôle modifié avec succès', true);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            throw new Error(data.message || 'Erreur lors du changement de rôle');
        }
    } catch (error) {
        showNotification(error.message, false);
        // Rétablir la sélection précédente en cas d'erreur
        const select = document.querySelector(`select[data-user-id="${userId}"]`);
        if (select) {
            select.value = select.getAttribute('data-current-role');
        }
    }
}

// Initialisation des événements
document.addEventListener('DOMContentLoaded', () => {
    // Gestion du clic en dehors de la modale
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    // Empêcher la propagation des clics sur la modale elle-même
    const modalContent = document.querySelector('.modal');
    if (modalContent) {
        modalContent.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    // Fermeture avec la touche Echap
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    // Stocker le rôle actuel pour chaque select
    document.querySelectorAll('select[name="role"]').forEach(select => {
        select.setAttribute('data-current-role', select.value);
        select.setAttribute('data-user-id', select.closest('tr').dataset.userId);
    });
});