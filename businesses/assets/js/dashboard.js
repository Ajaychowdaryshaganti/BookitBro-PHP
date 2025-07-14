document.addEventListener('DOMContentLoaded', function() {
  // Sidebar and Tab Switching
  const sidebarLinks = document.querySelectorAll('.sidebar nav a');
  const tabContents = document.querySelectorAll('.tab-content');

  function activateTab(tabName) {
    sidebarLinks.forEach(link => {
      if (link.getAttribute('data-tab') === tabName) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });

    tabContents.forEach(tc => {
      if (tc.getAttribute('data-tab') === tabName) {
        tc.style.display = '';
      } else {
        tc.style.display = 'none';
      }
    });
  }

  // On tab click, activate and save to localStorage
  sidebarLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const tabName = this.getAttribute('data-tab');
      activateTab(tabName);
      localStorage.setItem('activeTab', tabName);

      // If profile tab activated, load profile data
      if (tabName === 'profile') {
        loadProfileData();
      }
    });
  });

  // On page load, activate saved tab or default
  const savedTab = localStorage.getItem('activeTab');
  if (savedTab && document.querySelector(`.sidebar nav a[data-tab="${savedTab}"]`)) {
    activateTab(savedTab);
    if (savedTab === 'profile') loadProfileData();
  } else {
    const defaultTab = document.querySelector('.sidebar nav a.active') || sidebarLinks[0];
    if (defaultTab) {
      activateTab(defaultTab.getAttribute('data-tab'));
    }
  }

  // --- Bookings Tab ---

  const filterDateInput = document.getElementById('filterDate');
  const filterStatusSelect = document.getElementById('filterStatus');

  if (filterDateInput && !filterDateInput.value) {
    const today = new Date().toISOString().split('T')[0];
    filterDateInput.value = today;
  }

  function getFilters() {
    return {
      date: filterDateInput ? filterDateInput.value : '',
      status: filterStatusSelect ? filterStatusSelect.value : ''
    };
  }

  function fetchDashboardData() {
    const { date, status } = getFilters();
    let url = 'dashboard_data.php?';
    if (date) url += 'date=' + encodeURIComponent(date) + '&';
    if (status) url += 'status=' + encodeURIComponent(status);
    fetch(url)
      .then(res => res.json())
      .then(data => renderDashboard(data))
      .catch(err => console.error('Error fetching dashboard data:', err));
      
  }
  

  if (filterDateInput) filterDateInput.addEventListener('change', fetchDashboardData);
  if (filterStatusSelect) filterStatusSelect.addEventListener('change', fetchDashboardData);

  document.addEventListener('change', function(e) {
    if (e.target.classList.contains('status-select')) {
      const bookingId = e.target.getAttribute('data-booking-id');
      const newStatus = e.target.value;
      updateBookingStatus(bookingId, newStatus);
    }
  });

  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('delete-booking')) {
      const bookingId = e.target.getAttribute('data-booking-id');
      if (confirm('Are you sure you want to delete this booking?')) {
        deleteBooking(bookingId);
      }
    }
  });

  function renderDashboard(data) {
    // Business name
    const businessNameElem = document.getElementById('businessName');
    if (businessNameElem) {
      businessNameElem.textContent = data.business_name || 'My Business';
    }

    // Today's bookings widget
    const bookingsList = document.querySelector('.widget-bookings .widget-list');
    const bookingsValue = document.querySelector('.widget-bookings .widget-value');
    if (bookingsList && bookingsValue) {
      bookingsList.innerHTML = '';
      if (data.bookings && data.bookings.length > 0) {
        bookingsValue.textContent = data.bookings.length;
        data.bookings.forEach(b => {
          bookingsList.innerHTML += `<li><i class='fa-solid fa-user'></i> ${b.time} - ${b.customer_name}</li>`;
        });
      } else {
        bookingsValue.textContent = '0';
        bookingsList.innerHTML = `<div class="empty-graphic">
          <svg fill="none" viewBox="0 0 64 64"><circle cx="32" cy="32" r="30" fill="#ff9c3f" opacity=".15"/><rect x="18" y="28" width="28" height="8" rx="4" fill="#ff9c3f" opacity=".3"/></svg>
          <div>No bookings today</div>
        </div>`;
      }
    }

    // Revenue widget
    const revenueValue = document.querySelector('.widget-revenue .widget-value');
    if (revenueValue) {
      revenueValue.textContent = '₹' + (data.revenue || 0);
    }

    // Next appointment widget
    const nextWidgetList = document.querySelector('.widget-next .widget-list');
    if (nextWidgetList) {
      if (data.next) {
        nextWidgetList.innerHTML = `
          <li><i class='fa-solid fa-user'></i> ${data.next.time} - ${data.next.customer_name}</li>
          <li><i class='fa-solid fa-briefcase'></i> Service: ${data.next.service}</li>
          <li><i class='fa-solid fa-circle-check'></i> Status: ${data.next.status}</li>
        `;
      } else {
        nextWidgetList.innerHTML = `<div class="empty-graphic">
          <svg fill="none" viewBox="0 0 64 64"><circle cx="32" cy="32" r="30" fill="#ff9c3f" opacity=".15"/><rect x="18" y="28" width="28" height="8" rx="4" fill="#ff9c3f" opacity=".3"/></svg>
          <div>No upcoming appointments</div>
        </div>`;
      }
    }

    // Reviews widget
    const reviewsList = document.querySelector('.widget-reviews .widget-list');
    if (reviewsList) {
      reviewsList.innerHTML = '';
      if (data.reviews && data.reviews.length > 0) {
        data.reviews.forEach(r => {
          reviewsList.innerHTML += `<li>${'⭐'.repeat(r.rating)} <span style="color:#666">"${r.comment}"</span> - <i class='fa-solid fa-user'></i> ${r.customer_name}</li>`;
        });
      } else {
        reviewsList.innerHTML = `<div class="empty-graphic">
          <svg fill="none" viewBox="0 0 64 64"><circle cx="32" cy="32" r="30" fill="#ff9c3f" opacity=".15"/><rect x="18" y="28" width="28" height="8" rx="4" fill="#ff9c3f" opacity=".3"/></svg>
          <div>No reviews yet</div>
        </div>`;
      }
    }

    // All bookings tab
    const allBookingsBody = document.getElementById('allBookingsBody');
    if (allBookingsBody) {
      allBookingsBody.innerHTML = '';

      if (data.all_bookings && data.all_bookings.length > 0) {
        // Filter out completed bookings
        const visibleBookings = data.all_bookings;

        if (visibleBookings.length > 0) {
          visibleBookings.forEach((b) => {
            const combinedDateTimeStr = `${b.booking_date} ${b.time}`;
            const bookingDateTime = new Date(combinedDateTimeStr);

            const formattedDate = bookingDateTime.toLocaleDateString('en-GB', {
              day: '2-digit',
              month: '2-digit',
              year: '2-digit'
            });

            const row = document.createElement('tr');
            row.innerHTML = `
              <td>${b.customer_name}</td>
              <td>${b.service}</td>
              <td>${b.employee_name || 'Any'}</td>
              <td>${formattedDate}</td>
              <td>${b.time}</td>
              <td>
                <select class="status-select" data-booking-id="${b.id}">
                  <option value="pending"   ${b.status === 'pending'   ? 'selected' : ''}>Pending</option>
                  <option value="confirmed" ${b.status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                  <option value="completed" ${b.status === 'completed' ? 'selected' : ''}>Completed</option>
                  <option value="cancelled" ${b.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                </select>
              </td>
              <td>
                <button class="delete-booking" data-booking-id="${b.id}">Delete</button>
              </td>
            `;

            allBookingsBody.appendChild(row);
          });
        } else {
          allBookingsBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No active bookings found</td></tr>`;
        }
      } else {
        allBookingsBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No bookings found</td></tr>`;
      }
    }
  }

  function updateBookingStatus(bookingId, newStatus) {
    fetch('update_booking_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `booking_id=${encodeURIComponent(bookingId)}&status=${encodeURIComponent(newStatus)}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Booking status updated successfully!');
        fetchDashboardData();
      } else {
        alert('Failed to update booking status: ' + data.error);
      }
    })
    .catch(error => {
      console.error('Error updating booking status:', error);
      alert('An error occurred while updating booking status.');
    });
  }

  function deleteBooking(bookingId) {
    fetch('delete_booking.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `booking_id=${encodeURIComponent(bookingId)}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Booking deleted successfully!');
        fetchDashboardData();
      } else {
        alert('Failed to delete booking: ' + data.error);
      }
    })
    .catch(error => {
      console.error('Error deleting booking:', error);
      alert('An error occurred while deleting booking.');
    });
  }

  fetchDashboardData();

  // --- Staff Tab ---

  const staffTab = document.querySelector('.tab-content[data-tab="staff"]');
  if (!staffTab) return;

  const staffTableBody = staffTab.querySelector('tbody');
  const addStaffBtn = document.getElementById('addStaffBtn');
  const staffModal = document.getElementById('staffModal');
  const staffForm = document.getElementById('staffForm');
  const cancelStaffBtn = document.getElementById('cancelStaffBtn');

  function loadStaff() {
    fetch('get_staff.php')
      .then(res => res.json())
      .then(data => {
        staffTableBody.innerHTML = '';
        data.forEach(staff => {
          staffTableBody.innerHTML += `
            <tr data-id="${staff.id}">
              <td>${staff.name}</td>
              <td>${staff.email}</td>
              <td>${staff.mobile || ''}</td>
              <td>${staff.position || ''}</td>
              <td>
                <input type="checkbox" class="availability-toggle" data-id="${staff.id}" ${staff.available == 1 ? 'checked' : ''} />
              </td>
              <td>
                <button class="delete-staff" data-id="${staff.id}">Delete</button>
              </td>
            </tr>
          `;
        });
      })
      .catch(error => console.error('Error loading staff:', error));
  }

  function showStaffModal(staff = null) {
    if (staff) {
      staffForm.staffId.value = staff.id;
      staffForm.staffName.value = staff.name;
      staffForm.staffEmail.value = staff.email;
      staffForm.staffMobile.value = staff.mobile || '';
      staffForm.staffPosition.value = staff.position || '';
    } else {
      staffForm.reset();
      staffForm.staffId.value = '';
    }
    staffModal.style.display = 'block';
  }

  function hideStaffModal() {
    staffModal.style.display = 'none';
  }

  if (addStaffBtn) addStaffBtn.addEventListener('click', () => showStaffModal());
  if (cancelStaffBtn) cancelStaffBtn.addEventListener('click', hideStaffModal);

  if (staffForm) {
    staffForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const id = staffForm.staffId.value;
      const url = id ? 'update_staff.php' : 'add_staff.php';
      const formData = new URLSearchParams();
      formData.append('id', id);
      formData.append('name', staffForm.staffName.value);
      formData.append('email', staffForm.staffEmail.value);
      formData.append('mobile', staffForm.staffMobile.value);
      formData.append('position', staffForm.staffPosition.value);

      fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert('Staff saved successfully');
          hideStaffModal();
          loadStaff();
          activateTab('staff');
          localStorage.setItem('activeTab', 'staff');
        } else {
          alert('Error: ' + (data.error || 'Failed to save staff'));
        }
      })
      .catch(error => console.error('Error saving staff:', error));
    });
  }

  if (staffTableBody) {
    staffTableBody.addEventListener('click', function(e) {
      const target = e.target;
      const id = target.getAttribute('data-id');
      if (!id) return;

      if (target.classList.contains('delete-staff')) {
        if (confirm('Are you sure you want to delete this staff member?')) {
          fetch('delete_staff.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${encodeURIComponent(id)}`
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              alert('Staff deleted');
              loadStaff();
            } else {
              alert('Failed to delete staff');
            }
          })
          .catch(error => console.error('Error deleting staff:', error));
        }
      }
    });

    staffTableBody.addEventListener('change', function(e) {
      const target = e.target;
      if (target.classList.contains('availability-toggle')) {
        const id = target.getAttribute('data-id');
        const available = target.checked ? 1 : 0;

        fetch('toggle_availability.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `id=${encodeURIComponent(id)}&available=${available}`
        })
        .then(res => res.json())
        .then(data => {
          if (!data.success) {
            alert('Failed to update availability');
            loadStaff();
          }
        })
        .catch(error => console.error('Error toggling availability:', error));
      }
    });
  }
  // Profile tab sub-tabs toggle
const profileTabBtns = document.querySelectorAll('.profile-tab-btn');
const profileTabContents = document.querySelectorAll('.profile-tab-content');

profileTabBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    profileTabBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const tab = btn.getAttribute('data-profile-tab');
    profileTabContents.forEach(content => {
      content.style.display = content.id === tab ? 'block' : 'none';
    });
  });
});

const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
const workingHoursContainer = document.getElementById('workingHoursContainer');

function createDayRow(day, open = '09:00', close = '18:00', isOpen = true) {
  const div = document.createElement('div');
  div.className = 'day-row';
  div.innerHTML = `
    <div class="day-label">${day.charAt(0).toUpperCase() + day.slice(1)}</div>
    <input type="checkbox" name="${day}_open" ${isOpen ? 'checked' : ''}>
    <input type="time" class="time-input" name="${day}_open_time" value="${open}" ${isOpen ? '' : 'disabled'}>
    <input type="time" class="time-input" name="${day}_close_time" value="${close}" ${isOpen ? '' : 'disabled'}>
  `;

  const checkbox = div.querySelector(`input[name="${day}_open"]`);
  const openInput = div.querySelector(`input[name="${day}_open_time"]`);
  const closeInput = div.querySelector(`input[name="${day}_close_time"]`);
  checkbox.addEventListener('change', () => {
    openInput.disabled = !checkbox.checked;
    closeInput.disabled = !checkbox.checked;
  });

  return div;
}

function fillWorkingHours(jsonString) {
  let hours = {};
  try {
    hours = JSON.parse(jsonString);
  } catch {
    days.forEach(day => {
      hours[day] = { open: '09:00', close: '18:00' };
    });
  }
  workingHoursContainer.innerHTML = '';
  days.forEach(day => {
    const dayHours = hours[day];
    if (dayHours) {
      workingHoursContainer.appendChild(createDayRow(day, dayHours.open, dayHours.close, true));
    } else {
      workingHoursContainer.appendChild(createDayRow(day, '09:00', '18:00', false));
    }
  });
}

// Load profile data from backend
function loadProfileData() {
  fetch('get_profile_data.php')
    .then(res => res.json())
    .then(data => {
      // Fill business form
      const businessForm = document.getElementById('businessForm');
      if (businessForm && data.business) {
        businessForm.business_name.value = data.business.name || '';
        businessForm.address.value = data.business.address || '';
        businessForm.city.value = data.business.city || '';
        businessForm.state.value = data.business.state || '';
        businessForm.mobile.value = data.business.mobile || '';
        fillWorkingHours(data.business.working_hours || '{}');
        businessForm.slot_duration.value = data.business.slot_duration || 30;
        businessForm.category.value = data.business.category || '';
      }
      // Fill owner form
      const ownerForm = document.getElementById('ownerForm');
      if (ownerForm && data.owner) {
        ownerForm.owner_name.value = data.owner.name || '';
        ownerForm.owner_email.value = data.owner.email || '';
        ownerForm.owner_mobile.value = data.owner.mobile || '';
      }
      // Load moderators list
      loadModerators(data.moderators || []);
    })
    .catch(err => console.error('Error loading profile data:', err));
}

// Business form submit
document.getElementById('businessForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  const workingHours = {};
  days.forEach(day => {
    if (formData.get(`${day}_open`) === 'on') {
      const openTime = formData.get(`${day}_open_time`);
      const closeTime = formData.get(`${day}_close_time`);
      if (openTime && closeTime) {
        workingHours[day] = { open: openTime, close: closeTime };
      }
    }
  });
  formData.set('working_hours', JSON.stringify(workingHours));

  fetch('update_business.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    alert(data.success ? 'Business details updated successfully' : 'Failed to update business details: ' + (data.error || 'Unknown error'));
  })
  .catch(() => alert('Error updating business details'));
});

// Owner form submit
document.getElementById('ownerForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('update_owner.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    alert(data.success ? 'Owner details updated successfully' : 'Failed to update owner details: ' + (data.error || 'Unknown error'));
  })
  .catch(() => alert('Error updating owner details'));
});

// Moderators management
const moderatorsTableBody = document.querySelector('#moderatorsTable tbody');
const moderatorModal = document.getElementById('moderatorModal');
const moderatorForm = document.getElementById('moderatorForm');
const addModeratorBtn = document.getElementById('addModeratorBtn');
const cancelModeratorBtn = document.getElementById('cancelModeratorBtn');

function loadModerators(moderators) {
  moderatorsTableBody.innerHTML = '';
  moderators.forEach(mod => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${mod.name}</td>
      <td>${mod.email}</td>
      <td>${mod.mobile || ''}</td>
      <td><button class="delete-moderator" data-id="${mod.id}">Remove</button></td>
    `;
    moderatorsTableBody.appendChild(tr);
  });
}

addModeratorBtn.addEventListener('click', () => {
  moderatorForm.reset();
  moderatorModal.style.display = 'block';
});

cancelModeratorBtn.addEventListener('click', () => {
  moderatorModal.style.display = 'none';
});

moderatorForm.addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('add_moderator.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('Moderator added successfully');
      moderatorModal.style.display = 'none';
      loadProfileData();
    } else {
      alert('Failed to add moderator: ' + (data.error || 'Unknown error'));
    }
  })
  .catch(() => alert('Error adding moderator'));
});

moderatorsTableBody.addEventListener('click', function(e) {
  if (e.target.classList.contains('delete-moderator')) {
    const id = e.target.getAttribute('data-id');
    if (confirm('Remove this moderator?')) {
      fetch('remove_moderator.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${encodeURIComponent(id)}`
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert('Moderator removed');
          loadProfileData();
        } else {
          alert('Failed to remove moderator: ' + (data.error || 'Unknown error'));
        }
      })
      .catch(() => alert('Error removing moderator'));
    }
  }
});


const addBookingBtn = document.getElementById('addBookingBtn');
const bookingModal = document.getElementById('bookingModal');
const closeBookingModal = document.getElementById('closeBookingModal');
const bookingForm = document.getElementById('bookingForm');

// Open modal
addBookingBtn.addEventListener('click', () => {
  bookingModal.style.display = 'flex';
  loadEmployees();
});

// Close modal
closeBookingModal.addEventListener('click', () => {
  bookingModal.style.display = 'none';
});

// Close modal on outside click
window.addEventListener('click', e => {
  if (e.target === bookingModal) {
    bookingModal.style.display = 'none';
  }
});

// Load employees from backend
function loadEmployees() {
  fetch('get_staff.php')
    .then(res => res.json())
    .then(data => {
      const select = bookingForm.employee_id;
      select.innerHTML = '<option value="">Select Employee</option>';
      data.forEach(emp => {
        const option = document.createElement('option');
        option.value = emp.id;
        option.textContent = emp.name;
        select.appendChild(option);
      });
    })
    .catch(() => alert('Failed to load employees'));
}

// Submit booking form
bookingForm.addEventListener('submit', e => {
  e.preventDefault();
  const formData = new FormData(bookingForm);
  formData.append('user_id', 999999999);
  fetch('add_booking.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('Booking added successfully');
      bookingModal.style.display = 'none';
      bookingForm.reset();
      fetchDashboardData();
    } else {
      alert('Failed to add booking: ' + (data.error || 'Unknown error'));
    }
  })
  .catch(() => alert('Error adding booking'));
});

  loadStaff();
});