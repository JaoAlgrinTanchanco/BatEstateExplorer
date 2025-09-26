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

// Admin Agents (Direct + Associate)
document.addEventListener('DOMContentLoaded', () => {
  const agentList = document.getElementById('agentList');
  const agentModal = document.getElementById('agentModal');
  const modalBody = document.getElementById('modalBody');
  const modalCloseBtns = agentModal ? agentModal.querySelectorAll('.close, .cancel-btn') : [];
  const sortSelect = document.getElementById('sort');

  // Close modal functionality
  modalCloseBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      if (agentModal) agentModal.style.display = 'none';
    });
  });

  // Helper function to generate document link HTML
  function docLink(label, path, field) {
    if (!path) return '';
    const file = encodeURIComponent(path.split('/').pop()); // get filename only
    return `
      <div class="detail-row">
        <div class="detail-label">${label}:</div>
        <div class="detail-value">
          <a href="/BatEstateExplorer/public/api/view_document.php?file=${file}&field=${field}" 
            target="_blank" class="document-link">View</a>
        </div>
      </div>`;
  }

  // Agent list actions (View and Remove)
  if (agentList) {
    agentList.addEventListener('click', async (e) => {
      if (e.target.matches('.btn-view')) {
        const btn = e.target;

        let additionalDocsHtml = '';
        if (btn.dataset.additionalDocsPath) {
          const docs = btn.dataset.additionalDocsPath
            .split(',')
            .map(d => d.trim())
            .filter(d => d);

          additionalDocsHtml = docs.map((doc, i) => {
            const file = encodeURIComponent(doc.split('/').pop());
            return `
              <div class="detail-row">
                <div class="detail-label">Additional Document ${i + 1}:</div>
                <div class="detail-value">
                  <a href="/BatEstateExplorer/public/api/view_document.php?file=${file}&field=additional_docs_path" 
                    target="_blank" class="document-link">View</a>
                </div>
              </div>`;
          }).join('');
        }

        modalBody.innerHTML = `
          <div class="col-left">
            <section class="personal-info">
              <h3>Personal Information</h3>
              <div class="detail-row"><div class="detail-label">Full Name:</div><div class="detail-value">${btn.dataset.firstName} ${btn.dataset.lastName}</div></div>
              <div class="detail-row"><div class="detail-label">Email:</div><div class="detail-value">${btn.dataset.email}</div></div>
              <div class="detail-row"><div class="detail-label">Phone:</div><div class="detail-value">${btn.dataset.phone || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">Address:</div><div class="detail-value">${btn.dataset.address || 'N/A'}</div></div>
            </section>

            <section class="agent-info">
              <h3>Agent Information</h3>
              <div class="detail-row"><div class="detail-label">Agent Type:</div><div class="detail-value">${btn.dataset.userType || 'DIRECT AGENT'}</div></div>
              <div class="detail-row"><div class="detail-label">Broker ID:</div><div class="detail-value">${btn.dataset.brokerId || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">License Number:</div><div class="detail-value">${btn.dataset.licenseNumber || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">Experience:</div><div class="detail-value">${btn.dataset.experienceYears || 'N/A'} years</div></div>
              <div class="detail-row"><div class="detail-label">Specialization:</div><div class="detail-value">${btn.dataset.specialization || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">Company:</div><div class="detail-value">${btn.dataset.companyName || 'N/A'}</div></div>
            </section>
          </div>

          <div class="col-right">
            <section class="education">
              <h3>Education & Qualifications</h3>
              <div class="detail-row"><div class="detail-label">Education:</div><div class="detail-value">${btn.dataset.education || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">School:</div><div class="detail-value">${btn.dataset.school || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">Course:</div><div class="detail-value">${btn.dataset.course || 'N/A'}</div></div>
              <div class="detail-row"><div class="detail-label">Graduation Year:</div><div class="detail-value">${btn.dataset.graduationYear || 'N/A'}</div></div>
            </section>

            <section class="documents">
              <h3>Uploaded Documents</h3>
              ${docLink('Broker License', btn.dataset.brokerLicensePath, 'broker_license_path')}
              ${docLink('PRC License', btn.dataset.prcLicensePath, 'prc_license_path')}
              ${docLink('Resume/CV', btn.dataset.resumePath, 'resume_path')}
              ${docLink('Valid ID', btn.dataset.validIdPath, 'valid_id_path')}
              ${additionalDocsHtml || '<div class="detail-row"><div class="detail-value">No additional documents uploaded</div></div>'}
            </section>

            <section class="account">
              <h3>Account Information</h3>
              <div class="detail-row"><div class="detail-label">Account Created:</div><div class="detail-value">${new Date(btn.dataset.accountCreated).toLocaleDateString()}</div></div>
              <div class="detail-row"><div class="detail-label">Status:</div><div class="detail-value">${btn.dataset.status || 'N/A'}</div></div>
            </section>
          </div>
        `;

        agentModal.style.display = 'block';
      }

      else if (e.target.matches('.btn-remove')) {
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

  // Close modal if clicking outside
  window.addEventListener('click', e => {
    if (e.target === agentModal) {
      agentModal.style.display = 'none';
    }
  });
});

// Logout Modal Logic
const logoutLink = document.querySelector('.sidebar-footer a');
const logoutModal = document.getElementById('logoutModal');
const cancelBtn = document.getElementById('cancelLogout');

// Open modal instead of direct logout
logoutLink.addEventListener('click', function(e) {
  e.preventDefault();
  logoutModal.style.display = 'flex';
});

// Close modal on cancel
cancelBtn.addEventListener('click', function() {
  logoutModal.style.display = 'none';
});

// Close modal if clicking outside content
logoutModal.addEventListener('click', function(e) {
  if (e.target === logoutModal) {
    logoutModal.style.display = 'none';
  }
});