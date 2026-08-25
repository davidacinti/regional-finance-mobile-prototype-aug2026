(() => {
  const shell = document.querySelector('.prototype-shell');
  const themeToggle = document.querySelector('.theme-toggle');

  if (shell && themeToggle) {
    const savedTheme = window.localStorage.getItem('regionalPrototypeTheme') || 'light';

    const setTheme = theme => {
      const isDark = theme === 'dark';
      shell.dataset.prototypeTheme = theme;
      themeToggle.setAttribute('aria-pressed', String(isDark));
      themeToggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
      themeToggle.innerHTML = `<i class="ti ${isDark ? 'ti-sun' : 'ti-moon'}"></i>`;
      window.localStorage.setItem('regionalPrototypeTheme', theme);
    };

    setTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
      setTheme(shell.dataset.prototypeTheme === 'dark' ? 'light' : 'dark');
    });
  }

  document.querySelectorAll('[data-back-button]').forEach(button => {
    button.addEventListener('click', event => {
      if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }

      event.preventDefault();
      window.location.assign(button.getAttribute('href') || '/');
    });
  });

  document.querySelectorAll('[data-branch-map]').forEach(mapEl => {
    const lat = Number(mapEl.dataset.lat);
    const lng = Number(mapEl.dataset.lng);
    const zoom = Number(mapEl.dataset.zoom || 13);
    const mapUrl = mapEl.dataset.mapUrl;
    let pointerStart = null;

    if (!window.L || !Number.isFinite(lat) || !Number.isFinite(lng)) {
      if (mapUrl) {
        mapEl.addEventListener('click', () => window.open(mapUrl, '_blank', 'noopener'));
      }
      return;
    }

    mapEl.querySelector('.branch-map-fallback')?.remove();

    let mapCanvas = mapEl.querySelector('[data-branch-map-canvas]');

    if (!mapCanvas) {
      mapCanvas = document.createElement('div');
      mapCanvas.className = 'branch-map-canvas';
      mapCanvas.dataset.branchMapCanvas = '';
      mapEl.prepend(mapCanvas);
    }

    const map = window.L.map(mapCanvas, {
      zoomControl: false,
      attributionControl: true,
      dragging: true,
      scrollWheelZoom: false,
      doubleClickZoom: false,
      tap: true
    }).setView([lat, lng], Number.isFinite(zoom) ? zoom : 13);

    window.L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      maxZoom: 20,
      attribution: '&copy; OpenStreetMap &copy; CARTO'
    }).addTo(map);

    const branchIcon = window.L.divIcon({
      className: 'branch-map-marker',
      html: '<span></span>',
      iconSize: [42, 42],
      iconAnchor: [21, 38],
      popupAnchor: [0, -36]
    });

    window.L.marker([lat, lng], { icon: branchIcon })
      .addTo(map)
      .bindPopup(mapEl.dataset.title || 'Regional Finance branch');

    if (mapUrl) {
      mapEl.addEventListener('pointerdown', event => {
        pointerStart = { x: event.clientX, y: event.clientY };
      });

      mapEl.addEventListener('click', event => {
        const target = event.target;
        const isLeafletControl = target instanceof Element && target.closest('.leaflet-control-container');
        const moved = pointerStart && Math.hypot(event.clientX - pointerStart.x, event.clientY - pointerStart.y) > 8;

        if (!isLeafletControl && !moved) {
          window.open(mapUrl, '_blank', 'noopener');
        }
      });

      mapEl.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          window.open(mapUrl, '_blank', 'noopener');
        }
      });
    }

    window.setTimeout(() => map.invalidateSize(), 100);
  });

  const menuToggle = document.querySelector('[data-menu-toggle]');
  const sideMenu = document.querySelector('.side-menu');
  const sideMenuBackdrop = document.querySelector('.side-menu-backdrop');
  const menuCloseControls = document.querySelectorAll('[data-menu-close]');

  if (menuToggle && sideMenu && sideMenuBackdrop) {
    const setMenuOpen = isOpen => {
      sideMenu.classList.toggle('is-open', isOpen);
      sideMenu.setAttribute('aria-hidden', String(!isOpen));
      menuToggle.setAttribute('aria-expanded', String(isOpen));
      sideMenuBackdrop.hidden = !isOpen;

      if (isOpen) {
        sideMenu.querySelector('a, button')?.focus();
      } else if (document.activeElement && sideMenu.contains(document.activeElement)) {
        menuToggle.focus();
      }
    };

    setMenuOpen(false);

    menuToggle.addEventListener('click', () => {
      setMenuOpen(!sideMenu.classList.contains('is-open'));
    });

    menuCloseControls.forEach(control => {
      control.addEventListener('click', () => setMenuOpen(false));
    });

    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && sideMenu.classList.contains('is-open')) {
        setMenuOpen(false);
      }
    });

    window.addEventListener('pageshow', () => setMenuOpen(false));
  }

  const mockChat = document.querySelector('[data-mock-chat]');

  if (mockChat) {
    const chatThread = mockChat.querySelector('[data-chat-thread]');
    const chatForm = mockChat.querySelector('[data-chat-form]');
    const chatInput = mockChat.querySelector('[data-chat-input]');
    const responseText = 'This will be AI-powered in the app. For this prototype, I can only show how the chat experience could feel. A future version could answer account questions, guide payment next steps, explain documents, and connect customers with Regional Finance support.';

    const addMessage = (role, label, text) => {
      if (!chatThread) {
        return;
      }

      const message = document.createElement('article');
      message.className = `chat-bubble ${role}`;
      message.innerHTML = `<span>${label}</span><p>${text}</p>`;
      chatThread.appendChild(message);
      chatThread.scrollTop = chatThread.scrollHeight;
    };

    const submitMockMessage = text => {
      const cleanText = text.trim();

      if (!cleanText) {
        return;
      }

      addMessage('customer', 'You', cleanText);
      window.setTimeout(() => addMessage('assistant', 'Regional Assistant', responseText), 300);
    };

    chatForm?.addEventListener('submit', event => {
      event.preventDefault();
      submitMockMessage(chatInput?.value || '');

      if (chatInput) {
        chatInput.value = '';
        chatInput.focus();
      }
    });

    mockChat.querySelectorAll('[data-chat-prompt]').forEach(button => {
      button.addEventListener('click', () => submitMockMessage(button.dataset.chatPrompt || button.textContent || ''));
    });
  }

  document.querySelectorAll('[data-loan-activity]').forEach(activity => {
    const tabs = activity.querySelectorAll('[data-activity-tab]');
    const panels = activity.querySelectorAll('[data-activity-panel]');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const activePanel = tab.dataset.activityTab;

        tabs.forEach(candidate => {
          candidate.classList.toggle('active', candidate === tab);
        });

        panels.forEach(panel => {
          panel.hidden = panel.dataset.activityPanel !== activePanel;
        });
      });
    });
  });

  const showPrototypeAlert = ({ title, text, icon = 'ti-circle-check', confirmText = 'OK', showCancel = false, cancelText = 'Not now' }) => {
    if (window.Swal) {
      return window.Swal.fire({
        title,
        text,
        icon: icon.includes('x') ? 'warning' : 'success',
        confirmButtonText: confirmText,
        showCancelButton: showCancel,
        cancelButtonText: cancelText,
        customClass: {
          confirmButton: 'btn btn-primary',
          cancelButton: 'btn btn-light'
        },
        buttonsStyling: false
      });
    }

    return new Promise(resolve => {
      const backdrop = document.createElement('div');
      backdrop.className = 'prototype-swal-backdrop';
      backdrop.innerHTML = `
        <section class="prototype-swal" role="alertdialog" aria-modal="true">
          <i class="ti ${icon}"></i>
          <h2>${title}</h2>
          <p>${text}</p>
          <div class="button-row">
            ${showCancel ? `<button class="btn btn-light flex-fill" type="button" data-swal-cancel>${cancelText}</button>` : ''}
            <button class="btn btn-primary flex-fill" type="button" data-swal-confirm>${confirmText}</button>
          </div>
        </section>
      `;
      document.body.appendChild(backdrop);
      backdrop.querySelector('[data-swal-confirm]').focus();
      backdrop.querySelector('[data-swal-confirm]').addEventListener('click', () => {
        backdrop.remove();
        resolve({ isConfirmed: true });
      });
      backdrop.querySelector('[data-swal-cancel]')?.addEventListener('click', () => {
        backdrop.remove();
        resolve({ isConfirmed: false });
      });
    });
  };

  const paymentFlow = document.querySelector('[data-payment-flow]');

  if (paymentFlow) {
    const minimumDue = Number(paymentFlow.dataset.minimumDue || 0);
    const amountInput = paymentFlow.querySelector('#payment-amount');
    const dateInput = paymentFlow.querySelector('#payment-date');
    const warning = paymentFlow.querySelector('[data-minimum-warning]');
    const reviewButton = paymentFlow.querySelector('[data-review-payment]');
    const submitButton = paymentFlow.querySelector('[data-submit-payment]');
    const reviewPanel = paymentFlow.querySelector('[data-payment-review]');
    const reviewAmount = paymentFlow.querySelector('[data-review-amount]');
    const reviewDate = paymentFlow.querySelector('[data-review-date]');
    const reviewAccount = paymentFlow.querySelector('[data-review-account]');
    const reviewWarning = paymentFlow.querySelector('[data-review-warning]');
    const accountModeField = paymentFlow.querySelector('[data-account-mode]');
    const addBankToggle = paymentFlow.querySelector('[data-add-bank-toggle]');
    const addBankPanel = paymentFlow.querySelector('[data-add-bank-panel]');
    const bankDropdownToggle = paymentFlow.querySelector('[data-bank-dropdown-toggle]');
    const bankDropdownMenu = paymentFlow.querySelector('[data-bank-dropdown-menu]');
    const selectedBankName = paymentFlow.querySelector('[data-selected-bank-name]');
    const selectedBankMeta = paymentFlow.querySelector('[data-selected-bank-meta]');
    const savedAccountField = paymentFlow.querySelector('[data-saved-account]');
    const cancelPaymentForm = paymentFlow.querySelector('[data-cancel-payment]');

    const money = value => Number(value || 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    const formatDate = value => {
      if (!value) {
        return '';
      }

      const [year, month, day] = value.split('-').map(Number);
      return new Date(year, month - 1, day).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
      });
    };
    const selectedAccountLabel = () => {
      if (accountModeField?.value === 'new') {
        const name = paymentFlow.querySelector('[name="new_account_name"]')?.value.trim();
        const accountNumber = paymentFlow.querySelector('[name="new_account_number"]')?.value.trim();
        const suffix = accountNumber ? accountNumber.slice(-4) : 'new';
        return `${name || 'New checking account'} - ${suffix}`;
      }

      return savedAccountField?.value || 'Primary Checking - 4203';
    };
    const syncWarning = () => {
      if (!amountInput || !warning) {
        return false;
      }

      const belowMinimum = Number(amountInput.value || 0) < minimumDue;
      warning.hidden = !belowMinimum;

      return belowMinimum;
    };

    amountInput?.addEventListener('input', syncWarning);
    amountInput?.addEventListener('focus', () => {
      amountInput.value = '';
      syncWarning();
    });
    amountInput?.addEventListener('blur', () => {
      if (amountInput.value.trim() === '') {
        amountInput.value = minimumDue.toFixed(2);
        syncWarning();
      }
    });

    bankDropdownToggle?.addEventListener('click', () => {
      if (!bankDropdownMenu) {
        return;
      }

      bankDropdownMenu.hidden = !bankDropdownMenu.hidden;
      bankDropdownToggle.setAttribute('aria-expanded', String(!bankDropdownMenu.hidden));
    });

    paymentFlow.querySelectorAll('[data-account-option]').forEach(option => {
      option.addEventListener('click', () => {
        if (savedAccountField) {
          savedAccountField.value = option.dataset.accountLabel || 'Primary Checking - 4203';
        }

        if (selectedBankName) {
          selectedBankName.innerHTML = option.dataset.accountName || 'Primary Checking &bull; 4203';
        }

        if (selectedBankMeta) {
          selectedBankMeta.textContent = option.dataset.accountMeta || 'Checking account';
        }

        if (accountModeField) {
          accountModeField.value = 'saved';
        }

        if (bankDropdownMenu) {
          bankDropdownMenu.hidden = true;
        }

        bankDropdownToggle?.setAttribute('aria-expanded', 'false');
        if (addBankPanel) {
          addBankPanel.hidden = true;
        }
      });
    });

    addBankToggle?.addEventListener('click', () => {
      if (!addBankPanel) {
        return;
      }

      addBankPanel.hidden = !addBankPanel.hidden;
      if (!addBankPanel.hidden) {
        if (accountModeField) {
          accountModeField.value = 'new';
        }

        if (bankDropdownMenu) {
          bankDropdownMenu.hidden = true;
        }

        bankDropdownToggle?.setAttribute('aria-expanded', 'false');
        addBankPanel.querySelector('input')?.focus();
      } else if (accountModeField) {
        accountModeField.value = 'saved';
      }
    });

    reviewButton?.addEventListener('click', () => {
      const belowMinimum = syncWarning();

      if (!amountInput?.reportValidity() || !dateInput?.reportValidity()) {
        return;
      }

      if (reviewAmount) {
        reviewAmount.textContent = money(amountInput.value);
      }

      if (reviewDate) {
        reviewDate.textContent = formatDate(dateInput.value);
      }

      if (reviewAccount) {
        reviewAccount.textContent = selectedAccountLabel();
      }

      if (reviewWarning) {
        reviewWarning.hidden = !belowMinimum;
      }

      if (reviewPanel && submitButton) {
        reviewPanel.hidden = false;
        reviewButton.hidden = true;
        submitButton.hidden = false;
        reviewPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });

    if (paymentFlow.dataset.paymentStatus === 'scheduled') {
      showPrototypeAlert({
        title: 'Payment scheduled',
        text: `${paymentFlow.dataset.scheduledAmount} is pending for ${paymentFlow.dataset.scheduledDate}.`,
        icon: 'ti-clock-check',
        confirmText: 'View payment',
        showCancel: true,
        cancelText: 'Go home'
      }).then(result => {
        if (result.isDismissed || result.isConfirmed === false) {
          window.location.assign(paymentFlow.dataset.homeUrl || '/');
        }
      });
    }

    if (paymentFlow.dataset.paymentStatus === 'cancelled') {
      showPrototypeAlert({
        title: 'Payment cancelled',
        text: 'The pending payment was removed before it posted.',
        icon: 'ti-circle-x',
        confirmText: 'Done'
      });
    }

    cancelPaymentForm?.addEventListener('submit', event => {
      event.preventDefault();
      showPrototypeAlert({
        title: 'Cancel pending payment?',
        text: 'You can cancel this payment before it posts.',
        icon: 'ti-alert-circle',
        confirmText: 'Cancel payment',
        showCancel: true
      }).then(result => {
        if (result.isConfirmed) {
          cancelPaymentForm.submit();
        }
      });
    });

    syncWarning();
  }

  const stateNode = document.querySelector('[data-prototype-state]');

  if (stateNode) {
    try {
      const serverState = JSON.parse(stateNode.textContent || '{}');
      const storageKey = 'regionalPrototypeStateV2';
      const savedState = JSON.parse(window.localStorage.getItem(storageKey) || 'null');
      const serverRevision = Number(serverState?.meta?.revision || 0);
      const savedRevision = Number(savedState?.meta?.revision || 0);
      const syncKey = `regionalPrototypeSync:${savedRevision}`;

      if (savedState && savedRevision > serverRevision && !window.sessionStorage.getItem(syncKey)) {
        window.sessionStorage.setItem(syncKey, '1');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        window.fetch('/prototype/state/sync', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
          body: JSON.stringify({ state: savedState })
        }).then(response => {
          if (response.ok) window.location.reload();
        });
      } else {
        window.localStorage.setItem(storageKey, JSON.stringify(serverState));
      }
    } catch (error) {
      window.localStorage.removeItem('regionalPrototypeStateV2');
    }
  }

  const stateBuilder = document.querySelector('[data-state-builder]');

  if (stateBuilder) {
    let submitTimer;
    let saveQueue = Promise.resolve();
    let saveRequestId = 0;
    const storageKey = 'regionalPrototypeStateV2';
    const saveStatus = document.querySelector('[data-state-save-status]');
    const saveStatusText = saveStatus?.querySelector('span');

    const setSaveStatus = (label, isError = false) => {
      if (saveStatusText) saveStatusText.textContent = label;
      saveStatus?.classList.toggle('text-danger', isError);
      saveStatus?.querySelector('i')?.classList.toggle('ti-cloud-off', isError);
      saveStatus?.querySelector('i')?.classList.toggle('ti-cloud-check', !isError);
    };

    const readStateValue = (state, name) => {
      return name.replaceAll(']', '').split('[').reduce((value, key) => value?.[key], state);
    };

    const applyStateToBuilder = state => {
      stateBuilder.querySelectorAll('[name]').forEach(control => {
        if (control.type === 'hidden') return;
        const value = readStateValue(state, control.name);
        if (typeof value === 'undefined' || value === null) return;

        if (control.type === 'checkbox') {
          control.checked = Boolean(value);
        } else if (control.type === 'radio') {
          control.checked = String(control.value) === String(value);
        } else {
          control.value = String(value);
        }
      });

      const originationToggle = stateBuilder.querySelector('[data-origination-toggle]');
      const stepControl = stateBuilder.querySelector('[data-origination-step]');
      if (originationToggle && stepControl) stepControl.hidden = !originationToggle.checked;

      document.querySelectorAll('[data-preset-form]').forEach(form => {
        form.querySelector('.preset-button')?.classList.toggle('active', form.dataset.presetId === state?.meta?.preset);
      });
    };

    const queueSave = form => {
      const formData = new FormData(form);
      const requestId = ++saveRequestId;
      setSaveStatus('Saving...');

      saveQueue = saveQueue.catch(() => {}).then(async () => {
        const response = await window.fetch(form.action, {
          method: 'POST',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        });

        if (!response.ok) throw new Error('Unable to save prototype state.');
        const payload = await response.json();
        if (payload.state) {
          window.localStorage.setItem(storageKey, JSON.stringify(payload.state));
          if (requestId === saveRequestId) applyStateToBuilder(payload.state);
        }
        if (requestId === saveRequestId) setSaveStatus('Saved');
      }).catch(() => setSaveStatus('Try again', true));

      return saveQueue;
    };

    const submitBuilder = () => {
      window.clearTimeout(submitTimer);
      submitTimer = window.setTimeout(() => stateBuilder.requestSubmit(), 250);
    };

    stateBuilder.addEventListener('submit', event => {
      event.preventDefault();
      queueSave(stateBuilder);
    });

    document.querySelectorAll('[data-preset-form]').forEach(form => {
      form.addEventListener('submit', event => {
        event.preventDefault();
        window.clearTimeout(submitTimer);
        queueSave(form);
      });
    });

    stateBuilder.querySelectorAll('select, input[type="radio"], input[type="checkbox"]').forEach(control => {
      control.addEventListener('change', () => {
        if (control.matches('[data-origination-toggle]')) {
          const stepControl = stateBuilder.querySelector('[data-origination-step]');
          if (stepControl) stepControl.hidden = !control.checked;
        }
        submitBuilder();
      });
    });

    stateBuilder.querySelectorAll('[data-stepper]').forEach(stepper => {
      const input = stepper.querySelector('input[type="number"]');
      const changeValue = delta => {
        if (!input) return;
        const min = Number(input.min || 0);
        const max = Number(input.max || 99);
        input.value = String(Math.max(min, Math.min(max, Number(input.value || 0) + delta)));
        submitBuilder();
      };
      stepper.querySelector('[data-stepper-minus]')?.addEventListener('click', () => changeValue(-1));
      stepper.querySelector('[data-stepper-plus]')?.addEventListener('click', () => changeValue(1));
    });

    stateBuilder.querySelectorAll('input[type="number"]:not([readonly])').forEach(input => {
      input.addEventListener('change', submitBuilder);
    });
  }

  document.querySelector('[data-reset-prototype]')?.addEventListener('submit', () => {
    window.localStorage.removeItem('regionalPrototypeStateV2');
  });

  const modal = document.querySelector('.prototype-modal');

  if (!modal) {
    return;
  }

  const focusable = modal.querySelector('a, button');
  focusable?.focus();

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      modal.querySelector('.modal-close')?.click();
    }
  });
})();
