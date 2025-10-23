// Sidebar Navigation Highlight
document.addEventListener("DOMContentLoaded", () => {
  const navLinks = document.querySelectorAll(".sidebar-nav a");

  navLinks.forEach(link => {
    link.addEventListener("click", () => {
      navLinks.forEach(l => l.classList.remove("active"));
      link.classList.add("active");
      // Store active link for persistence on reload
      localStorage.setItem("activeNav", link.getAttribute("href"));
    });
  });

  // Restore active nav from localStorage
  const savedNav = localStorage.getItem("activeNav");
  if (savedNav) {
    const activeLink = document.querySelector(`.sidebar-nav a[href="${savedNav}"]`);
    if (activeLink) activeLink.classList.add("active");
  }
});

// Responsive Sidebar
const sidebar = document.querySelector(".sidebar-wrapper");
const toggleBtn = document.querySelector(".sidebar-toggle-btn");
const toggleIcon = toggleBtn.querySelector("i");
const backdrop = document.querySelector(".sidebar-backdrop");

const toggleGap = 12; // Gap between sidebar edge and toggle when open (in px)

function openSidebar() {
  sidebar.classList.add("open");
  sidebar.classList.remove("collapsed");
  backdrop.classList.add("show");
  toggleBtn.style.left = `${260 + toggleGap}px`;
  toggleIcon.classList.replace("fa-chevron-right", "fa-chevron-left");
}

function closeSidebar() {
  sidebar.classList.remove("open");
  backdrop.classList.remove("show");
  toggleBtn.style.left = "0.75rem";
  toggleIcon.classList.replace("fa-chevron-left", "fa-chevron-right");
}

toggleBtn.addEventListener("click", () => {
  if (sidebar.classList.contains("open")) {
    closeSidebar();
  } else {
    openSidebar();
  }
});

backdrop.addEventListener("click", closeSidebar);

// Handle responsive state on resize
function handleResize() {
  if (window.innerWidth <= 1024) {
    // Mobile/Tablet mode: force expanded and start hidden off-canvas
    sidebar.classList.remove("collapsed");
    closeSidebar();
    toggleBtn.style.display = "flex";
  } else {
    // Desktop mode: collapsed by default with hover-expand, hide floating toggle
    sidebar.classList.add("collapsed");
    sidebar.classList.remove("open");
    backdrop.classList.remove("show");
    toggleBtn.style.display = "none";
  }
}

handleResize(); // Run once at load
window.addEventListener("resize", handleResize);

// Dropdowns
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      this.parentElement.classList.toggle('open');
    });
  });
});

// Admin Agents (List, Remove, Sort)
document.addEventListener('DOMContentLoaded', () => {
  const agentList = document.getElementById('agentList');
  const sortSelect = document.getElementById('sort');

  if (agentList) {
    agentList.addEventListener('click', async (e) => {

      // 🔹 Modal view logic removed
      // Now handled by direct_modal_detail.js / associate_modal_detail.js

      if (e.target.matches('.btn-remove')) {
        const agentId = e.target.dataset.agentId;
        if (!agentId) return;

        if (!confirm('Are you sure you want to remove this agent account? This action cannot be undone.')) return;

        try {
          const response = await fetch('/BatEstateExplorer/database/remove_agent.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ agentId })
          });

          const data = await response.json();

          if (response.ok && data.success) {
            const card = e.target.closest('.direct-agent-card, .associate-agent-card');
            if (card) card.remove();
            alert('Agent account removed successfully.');
          } else {
            alert('Failed to remove agent: ' + (data.error || 'Unknown error'));
          }
        } catch (error) {
          alert('Error removing agent: ' + error.message);
        }
      }
    });

    // Sorting functionality
    if (sortSelect) {
      sortSelect.addEventListener('change', () => {
        const sortBy = sortSelect.value;
        const cards = Array.from(agentList.querySelectorAll('.direct-agent-card, .associate-agent-card'));
        let sorted;

        switch (sortBy) {
          case 'date':
            sorted = cards.sort((a, b) => new Date(b.dataset.date) - new Date(a.dataset.date));
            break;
          case 'name':
            sorted = cards.sort((a, b) => a.dataset.name.localeCompare(b.dataset.name));
            break;
          case 'experience':
            sorted = cards.sort((a, b) => parseInt(b.dataset.experience) - parseInt(a.dataset.experience));
            break;
        }

        sorted.forEach(card => agentList.appendChild(card));
      });
    }
  }
});

// Logout Modal Logic (safe version)
const logoutLink = document.querySelector('.sidebar-footer a');
const logoutModal = document.getElementById('logoutModal');
const cancelBtn = document.getElementById('cancelLogout');

if (logoutLink && logoutModal) {
  logoutLink.addEventListener('click', function (e) {
    e.preventDefault();
    logoutModal.style.display = 'flex';
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', function () {
      logoutModal.style.display = 'none';
    });
  }

  logoutModal.addEventListener('click', function (e) {
    if (e.target === logoutModal) {
      logoutModal.style.display = 'none';
    }
  });
}
