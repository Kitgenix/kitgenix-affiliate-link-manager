(function () {
  function setCopiedState(btn) {
    if (!btn) return;
    btn.textContent = btn.getAttribute('data-copied-label') || 'Copied';
    window.setTimeout(function () {
      btn.textContent = btn.getAttribute('data-default-label') || 'Copy';
    }, 3000);
  }

  function slugify(input) {
    input = (input || '').toString().trim().toLowerCase();
    if (!input) return '';
    // Basic slugify: letters/numbers/spaces to dashes, strip invalid.
    input = input.normalize ? input.normalize('NFKD') : input;
    input = input.replace(/[\u0300-\u036f]/g, '');
    input = input.replace(/[^a-z0-9\s-]/g, '');
    input = input.replace(/\s+/g, '-');
    input = input.replace(/-+/g, '-');
    input = input.replace(/^-|-$/g, '');
    return input;
  }

  function bindAutoSlug() {
    var titleEl = document.getElementById('kitgenix_affiliate_title');
    var slugEl = document.getElementById('kitgenix_affiliate_slug');
    if (!titleEl || !slugEl) return;

    var slugDirty = false;

    slugEl.addEventListener('input', function () {
      // If the user types anything, treat as manual override.
      slugDirty = (slugEl.value || '').trim().length > 0;
    });

    titleEl.addEventListener('input', function () {
      if (slugDirty) return;
      var s = slugify(titleEl.value);
      if (!s) return;
      slugEl.value = s;
    });
  }

  function bindInlineValidation() {
    function validateSlug(slugEl) {
      if (!slugEl) return;
      var v = (slugEl.value || '').toString().trim();
      if (!v) {
        slugEl.setCustomValidity('');
        return;
      }

      // WordPress sanitize_title produces lowercase a-z0-9-.
      if (!/^[a-z0-9-]+$/.test(v)) {
        slugEl.setCustomValidity('Slug can only contain lowercase letters, numbers, and dashes.');
        return;
      }

      slugEl.setCustomValidity('');
    }

    function validateDestination(destEl) {
      if (!destEl) return;
      var v = (destEl.value || '').toString().trim();
      if (!v) {
        destEl.setCustomValidity('Please enter a destination URL.');
        return;
      }

      if (!/^https?:\/\//i.test(v)) {
        destEl.setCustomValidity('Destination URL must start with http:// or https://');
        return;
      }

      try {
        // Basic URL sanity check.
        // eslint-disable-next-line no-new
        new URL(v);
      } catch (e) {
        destEl.setCustomValidity('Please enter a valid URL.');
        return;
      }

      destEl.setCustomValidity('');
    }

    function bindForm(form, slugSelector, destSelector) {
      if (!form) return;
      var slugEl = form.querySelector(slugSelector);
      var destEl = form.querySelector(destSelector);

      if (slugEl) {
        slugEl.addEventListener('input', function () {
          validateSlug(slugEl);
        });
        validateSlug(slugEl);
      }

      if (destEl) {
        destEl.addEventListener('input', function () {
          validateDestination(destEl);
        });
        destEl.addEventListener('blur', function () {
          // Trim accidental whitespace.
          destEl.value = (destEl.value || '').toString().trim();
          validateDestination(destEl);
        });
        validateDestination(destEl);
      }

      form.addEventListener('submit', function (e) {
        validateSlug(slugEl);
        validateDestination(destEl);

        if (slugEl && !slugEl.checkValidity()) {
          e.preventDefault();
          slugEl.reportValidity();
          slugEl.focus();
          return;
        }

        if (destEl && !destEl.checkValidity()) {
          e.preventDefault();
          destEl.reportValidity();
          destEl.focus();
        }
      });
    }

    // Main add/edit form.
    bindForm(
      document.getElementById('kitgenix-affiliate-add-form'),
      'input[name="slug"]',
      'input[name="destination_url"]'
    );

    // Modal form.
    bindForm(
      document.getElementById('kitgenix-affiliate-edit-form'),
      'input[name="slug"]',
      'input[name="destination_url"]'
    );
  }

  /**
   * The Edit-link modal needs its form fields populated from the clicked
   * row's data-* attributes before it opens, so it can't use the shared
   * library's generic data-kitgenix-modal-target trigger directly. Opening
   * and closing itself is still delegated to window.KitgenixAdminUI so the
   * modal behaves identically to every other Kitgenix modal (Escape key,
   * backdrop click, focus return) once the fields are filled in.
   */
  function bindModalEditor() {
    var modal = document.getElementById('kitgenix-affiliate-edit-modal');
    if (!modal) return;

    var form = document.getElementById('kitgenix-affiliate-edit-form');
    var deleteBtn = document.getElementById('kitgenix-affiliate-modal-delete');

    function fillModal(data) {
      if (!form) return;

      form.querySelector('input[name="post_id"]').value = data.id || '';
      form.querySelector('input[name="title"]').value = data.title || '';
      form.querySelector('input[name="slug"]').value = data.slug || '';
      form.querySelector('input[name="destination_url"]').value = data.destination || '';

      var startsAtEl = form.querySelector('input[name="starts_at"]');
      if (startsAtEl) startsAtEl.value = data.startsAt || '';

      var expiresAtEl = form.querySelector('input[name="expires_at"]');
      if (expiresAtEl) expiresAtEl.value = data.expiresAt || '';

      var fallbackEl = form.querySelector('input[name="fallback_url"]');
      if (fallbackEl) fallbackEl.value = data.fallbackUrl || '';

      var rotationModeEl = form.querySelector('select[name="rotation_mode"]');
      if (rotationModeEl) rotationModeEl.value = data.rotationMode || 'none';

      var rotationDestinationsEl = form.querySelector('textarea[name="rotation_destinations"]');
      if (rotationDestinationsEl) rotationDestinationsEl.value = data.rotationDestinations || '';

      var groupEl = form.querySelector('input[name="group_name"]');
      if (groupEl) groupEl.value = data.groupName || '';

      var tagsEl = form.querySelector('input[name="tag_names"]');
      if (tagsEl) tagsEl.value = data.tags || '';

      var noteEl = form.querySelector('textarea[name="campaign_note"]');
      if (noteEl) noteEl.value = data.campaignNote || '';

      var relEl = form.querySelector('select[name="rel"]');
      if (relEl) relEl.value = data.rel || 'nofollow sponsored';

      var enabledEl = form.querySelector('input[name="enabled"]');
      if (enabledEl) enabledEl.checked = (data.enabled !== '0');

      var clicksEl = form.querySelector('input[name="clicks"]');
      if (clicksEl) clicksEl.value = data.clicks || '0';

      if (deleteBtn) {
        deleteBtn.setAttribute('href', data.deleteUrl || '#');
        deleteBtn.style.display = data.deleteUrl ? '' : 'none';
      }
    }

    document.addEventListener('click', function (e) {
      var t = e.target;
      if (t && t.nodeType === 3) t = t.parentElement;
      if (!t || !t.closest) return;

      var edit = t.closest('[data-kitgenix-edit]');
      if (!edit) return;

      e.preventDefault();

      fillModal({
        id: edit.getAttribute('data-link-id') || '',
        title: edit.getAttribute('data-title') || '',
        slug: edit.getAttribute('data-slug') || '',
        destination: edit.getAttribute('data-destination') || '',
        startsAt: edit.getAttribute('data-starts-at') || '',
        expiresAt: edit.getAttribute('data-expires-at') || '',
        fallbackUrl: edit.getAttribute('data-fallback-url') || '',
        rotationMode: edit.getAttribute('data-rotation-mode') || 'none',
        rotationDestinations: edit.getAttribute('data-rotation-destinations') || '',
        groupName: edit.getAttribute('data-group-name') || '',
        tags: edit.getAttribute('data-tags') || '',
        campaignNote: edit.getAttribute('data-campaign-note') || '',
        rel: edit.getAttribute('data-rel') || '',
        enabled: edit.getAttribute('data-enabled') || '1',
        clicks: edit.getAttribute('data-clicks') || '0',
        deleteUrl: edit.getAttribute('data-delete-url') || ''
      });

      if (window.KitgenixAdminUI && typeof window.KitgenixAdminUI.openModal === 'function') {
        window.KitgenixAdminUI.openModal('#kitgenix-affiliate-edit-modal');
      }

      var first = document.getElementById('kitgenix_affiliate_modal_title');
      if (first) {
        window.setTimeout(function () {
          first.focus();
        }, 20);
      }
    });

    if (deleteBtn) {
      deleteBtn.addEventListener('click', function (e) {
        var href = deleteBtn.getAttribute('href') || '';
        if (!href || href === '#') {
          e.preventDefault();
          return;
        }
        if (!window.confirm('Move this link to the Trash?')) {
          e.preventDefault();
        }
      });
    }

    // Slug auto-generation in the modal too.
    var titleEl = document.getElementById('kitgenix_affiliate_modal_title');
    var slugEl = document.getElementById('kitgenix_affiliate_modal_slug');
    if (titleEl && slugEl) {
      var dirty = false;
      slugEl.addEventListener('input', function () {
        dirty = (slugEl.value || '').trim().length > 0;
      });
      titleEl.addEventListener('input', function () {
        if (dirty) return;
        var s = slugify(titleEl.value);
        if (!s) return;
        slugEl.value = s;
      });
    }
  }

  function onCopyClick(e) {
    var target = e.target;
    // If the click lands on a text node, closest() isn't available.
    if (target && target.nodeType === 3) {
      target = target.parentElement;
    }

    if (!target || !target.closest) return;
    var btn = target.closest('[data-kitgenix-copy-text]');
    if (!btn) return;
    e.preventDefault();

    var value = btn.getAttribute('data-kitgenix-copy-text') || '';
    if (!value) return;

    if (navigator && navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(value).then(function () {
        setCopiedState(btn);
      }).catch(function () {
        // Fall back if Clipboard API is blocked.
        try {
          var ta1 = document.createElement('textarea');
          ta1.value = value;
          ta1.style.position = 'fixed';
          ta1.style.left = '-9999px';
          document.body.appendChild(ta1);
          ta1.select();
          document.execCommand('copy');
          document.body.removeChild(ta1);
          setCopiedState(btn);
        } catch (err1) {}
      });
      return;
    }

    // Fallback
    var ta = document.createElement('textarea');
    ta.value = value;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      setCopiedState(btn);
    } catch (err) {}
    document.body.removeChild(ta);
  }

  function bindLinksSearch() {
    var input = document.getElementById('kitgenix-affiliate-links-search');
    var table = document.getElementById('kitgenix-affiliate-links-table');
    if (!input || !table) return;

    // Server-side search/pagination: do not apply client-side filtering.
    if ((input.getAttribute('data-kitgenix-server-search') || '') === '1') {
      return;
    }

    var clearBtn = document.getElementById('kitgenix-affiliate-links-clear');
    var countEl = document.getElementById('kitgenix-affiliate-links-count');
    var emptyEl = document.getElementById('kitgenix-affiliate-links-no-results');

    var tbody = table.querySelector('tbody');
    if (!tbody) return;

    function rows() {
      return Array.prototype.slice.call(tbody.querySelectorAll('tr'));
    }

    function norm(s) {
      return (s || '').toString().trim().toLowerCase();
    }

    function update() {
      var q = norm(input.value);
      var all = rows();
      var visible = 0;

      for (var i = 0; i < all.length; i++) {
        var r = all[i];
        var blob = norm(r.getAttribute('data-kitgenix-search') || r.textContent || '');
        var show = !q || (blob.indexOf(q) !== -1);
        r.style.display = show ? '' : 'none';
        if (show) visible++;
      }

      if (clearBtn) {
        clearBtn.style.display = q ? '' : 'none';
      }

      if (countEl) {
        if (!q) {
          countEl.textContent = '';
        } else {
          countEl.textContent = visible + ' / ' + all.length;
        }
      }

      if (emptyEl) {
        emptyEl.hidden = (visible !== 0);
      }
    }

    input.addEventListener('input', update);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        input.value = '';
        update();
        input.blur();
      }
    });

    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        input.value = '';
        update();
        input.focus();
      });
    }

    update();
  }

  function bindBulkValueToggle() {
    var select = document.getElementById('kitgenix_bulk_action');
    var input = document.getElementById('kitgenix_bulk_value');
    if (!select || !input) return;

    function sync() {
      var option = select.options[select.selectedIndex];
      var placeholder = option ? option.getAttribute('data-kitgenix-bulk-needs-value') : null;
      if (placeholder) {
        input.style.display = '';
        input.placeholder = placeholder;
        input.setAttribute('aria-hidden', 'false');
      } else {
        input.style.display = 'none';
        input.value = '';
        input.setAttribute('aria-hidden', 'true');
      }
    }

    select.addEventListener('change', sync);
    sync();
  }

  function bindBulkSelectAll() {
    var selectAll = document.getElementById('kitgenix-select-all-links');
    var table = document.getElementById('kitgenix-affiliate-links-table');
    if (!selectAll || !table) return;

    selectAll.addEventListener('change', function () {
      var boxes = table.querySelectorAll('tbody input[type="checkbox"][name="link_ids[]"]');
      for (var i = 0; i < boxes.length; i++) {
        boxes[i].checked = !!selectAll.checked;
      }
    });
  }

  // Dismiss button for .kitgenix-notice banners (server-rendered admin
  // notices adopting the shared notice component).
  function bindNoticeDismiss() {
    document.addEventListener('click', function (e) {
      var target = e.target;
      if (target && target.nodeType === 3) target = target.parentElement;
      if (!target || !target.closest) return;

      var closeBtn = target.closest('.kitgenix-notice-close');
      if (!closeBtn) return;

      var notice = closeBtn.closest('.kitgenix-notice');
      if (notice) notice.remove();
    });
  }

  document.addEventListener('click', onCopyClick);
  document.addEventListener('DOMContentLoaded', bindAutoSlug);
  document.addEventListener('DOMContentLoaded', bindModalEditor);
  document.addEventListener('DOMContentLoaded', bindLinksSearch);
  document.addEventListener('DOMContentLoaded', bindBulkSelectAll);
  document.addEventListener('DOMContentLoaded', bindBulkValueToggle);
  document.addEventListener('DOMContentLoaded', bindInlineValidation);
  bindNoticeDismiss();
})();
