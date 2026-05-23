/* ============================================================
   Petals & Bloom — Public JavaScript + jQuery interactions
   ============================================================ */

$(function () {

  /* ── Order form validation (P4) ── */
  const $orderForm = $('#orderForm');

  if ($orderForm.length) {

    $orderForm.on('submit', function (e) {
      e.preventDefault();
      let valid = true;

      // Helper: show/clear field error
      function showError($field, msg) {
        $field.addClass('is-invalid');
        $field.next('.invalid-feedback').text(msg).show();
        valid = false;
      }
      function clearError($field) {
        $field.removeClass('is-invalid');
        $field.next('.invalid-feedback').hide();
      }

      // 1. Name required
      const $name = $('#customer_name');
      clearError($name);
      if ($.trim($name.val()).length < 2) {
        showError($name, 'Full name must be at least 2 characters.');
      }

      // 2. Valid email
      const $email = $('#customer_email');
      clearError($email);
      const emailReg = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailReg.test($.trim($email.val()))) {
        showError($email, 'Please enter a valid email address.');
      }

      // 3. Phone at least 10 digits
      const $phone = $('#customer_phone');
      clearError($phone);
      const digits = $phone.val().replace(/\D/g, '');
      if (digits.length < 10) {
        showError($phone, 'Phone number must have at least 10 digits.');
      }

      // 4. Delivery date must be in the future
      const $date = $('#delivery_date');
      clearError($date);
      const today = new Date(); today.setHours(0, 0, 0, 0);
      const chosen = new Date($date.val());
      if (!$date.val() || chosen <= today) {
        showError($date, 'Please choose a delivery date at least one day in the future.');
      }

      // 5. At least one product selected
      const checkedProducts = $('input[name="products[]"]:checked').length;
      const $prodError = $('#products-error');
      if (checkedProducts === 0) {
        $prodError.text('Please select at least one product.').show();
        valid = false;
      } else {
        $prodError.hide();
      }

      // 6. Terms accepted
      const $terms = $('#agree_terms');
      clearError($terms);
      if (!$terms.is(':checked')) {
        showError($terms, 'You must agree to the terms and conditions.');
      }

      if (valid) {
        // Show loading spinner using jQuery .html()
        $('#submitBtn').prop('disabled', true)
          .html('<span class="spinner-border spinner-border-sm me-2"></span>Placing Order…');
        this.submit();
      } else {
        // Scroll to first error
        const $firstError = $orderForm.find('.is-invalid').first();
        if ($firstError.length) {
          $('html, body').animate({ scrollTop: $firstError.offset().top - 100 }, 400);
        }
      }
    });

    // Real-time qty total preview
    $(document).on('change', 'input[name="products[]"], input[class="qty-input"]', updateTotal);

    function updateTotal() {
      let total = 0;
      $('input[name="products[]"]:checked').each(function () {
        const price = parseFloat($(this).data('price')) || 0;
        const qty   = parseInt($(this).closest('.product-checkbox-item').find('.qty-input').val()) || 1;
        total += price * qty;
      });
      $('#orderTotal').text(total.toFixed(2) + ' RON');
      $('#orderTotalWrapper').toggle(total > 0);
    }
  }

  /* ── Registration form validation ── */
  const $regForm = $('#registrationForm');
  if ($regForm.length) {
    $regForm.on('submit', function (e) {
      e.preventDefault();
      let valid = true;

      function chk($f, condition, msg) {
        $f.removeClass('is-invalid');
        $f.next('.invalid-feedback').hide();
        if (condition) {
          $f.addClass('is-invalid');
          $f.next('.invalid-feedback').text(msg).show();
          valid = false;
        }
      }

      const emailReg = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      chk($('#reg_first_name'), $('#reg_first_name').val().trim().length < 2, 'First name is too short.');
      chk($('#reg_last_name'),  $('#reg_last_name').val().trim().length < 2,  'Last name is too short.');
      chk($('#reg_email'),      !emailReg.test($('#reg_email').val().trim()),  'Enter a valid email.');
      chk($('#reg_phone'),      $('#reg_phone').val().replace(/\D/g,'').length < 10, 'Enter a valid phone number.');
      chk($('#reg_password'),   $('#reg_password').val().length < 8,           'Password must be at least 8 characters.');
      chk($('#reg_confirm'),    $('#reg_confirm').val() !== $('#reg_password').val(), 'Passwords do not match.');

      if (valid) {
        $('#regSubmitBtn').prop('disabled', true)
          .html('<span class="spinner-border spinner-border-sm me-2"></span>Registering…');
        this.submit();
      }
    });
  }

  /* ── Login form validation ── */
  const $loginForm = $('#loginForm');
  if ($loginForm.length) {
    $loginForm.on('submit', function (e) {
      let valid = true;
      $('#login_email, #login_password').each(function () {
        if ($.trim($(this).val()) === '') {
          $(this).addClass('is-invalid');
          valid = false;
        } else {
          $(this).removeClass('is-invalid');
        }
      });
      if (!valid) {
        e.preventDefault();
        $('#loginError').text('Please fill in all fields.').show();
      }
    });
  }

  /* ── Product quantity stepper ── */
  $(document).on('click', '.qty-minus', function () {
    const $inp = $(this).siblings('.qty-input');
    let v = parseInt($inp.val()) || 1;
    if (v > 1) { $inp.val(v - 1).trigger('change'); }
  });
  $(document).on('click', '.qty-plus', function () {
    const $inp = $(this).siblings('.qty-input');
    let v = parseInt($inp.val()) || 1;
    $inp.val(v + 1).trigger('change');
  });

  /* ── Status lookup: enter triggers search ── */
  $('#statusSearchInput').on('keypress', function (e) {
    if (e.which === 13) { $('#statusSearchBtn').trigger('click'); }
  });

  /* ── Auto-dismiss success alerts after 5 s ── */
  setTimeout(function () {
    $('.alert-success').fadeOut('slow');
  }, 5000);

  /* ── Smooth scroll to sections ── */
  $('a[href^="#"]').on('click', function (e) {
    const target = $(this.getAttribute('href'));
    if (target.length) {
      e.preventDefault();
      $('html, body').animate({ scrollTop: target.offset().top - 80 }, 500);
    }
  });

});
