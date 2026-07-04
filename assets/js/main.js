(function () {
  'use strict';

  function togglePassword(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
    var icon = btn.querySelector('.eye-icon');
    var offIcon = btn.querySelector('.eye-off-icon');
    if (icon && offIcon) {
      icon.classList.toggle('hidden');
      offIcon.classList.toggle('hidden');
    }
  }
  window.togglePassword = togglePassword;

  function initRegisterForm() {
    var form = document.getElementById('registerForm');
    if (!form) return;

    var currentStep = 1;
    var totalSteps = 4;
    var steps = form.querySelectorAll('.step');
    var dots = document.querySelectorAll('.step-dot');
    var stepLabel = document.getElementById('stepLabel');
    var stepLabels = ['Enter your name', 'Basic information', 'Contact number', 'Set your password'];
    var backBtn = document.getElementById('backBtn');
    var nextBtn = document.getElementById('nextBtn');
    var submitBtn = document.getElementById('submitBtn');
    var googleSignUp = document.getElementById('googleSignUp');
    var currentStepInput = document.getElementById('currentStep');

    function showStep(step) {
      steps.forEach(function (el) {
        var s = parseInt(el.getAttribute('data-step'), 10);
        el.classList.toggle('hidden', s !== step);
      });
      dots.forEach(function (dot) {
        var s = parseInt(dot.getAttribute('data-step'), 10);
        dot.className = 'h-1 flex-1 rounded-full step-dot ' + (s <= step ? 'bg-google-blue' : 'bg-black/20');
      });
      if (stepLabel) stepLabel.textContent = stepLabels[step - 1];
      if (backBtn) backBtn.classList.toggle('hidden', step === 1);
      if (nextBtn) nextBtn.classList.toggle('hidden', step === totalSteps);
      if (submitBtn) submitBtn.classList.toggle('hidden', step !== totalSteps);
      if (googleSignUp) googleSignUp.classList.toggle('hidden', step !== 1);
      if (currentStepInput) currentStepInput.value = step;
      currentStep = step;
    }

    function validateStep(step) {
      var fieldset = form.querySelector('.step[data-step="' + step + '"]');
      if (!fieldset) return true;
      var inputs = fieldset.querySelectorAll('input, select');
      var valid = true;
      inputs.forEach(function (input) {
        if (input.hasAttribute('data-optional')) return;
        if (input.hasAttribute('required') && !input.value.trim()) {
          valid = false;
          input.style.borderColor = 'red';
        } else {
          input.style.borderColor = '';
        }
        if (input.type === 'email' && input.value.trim()) {
          var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!re.test(input.value.trim())) valid = false;
        }
      });
      if (step === totalSteps) {
        var pw = form.querySelector('input[name="password"]');
        var cpw = form.querySelector('input[name="confirm_password"]');
        if (pw && pw.value.length < 8) valid = false;
        if (pw && cpw && pw.value !== cpw.value) valid = false;
      }
      return valid;
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (!validateStep(currentStep)) return;
        if (currentStep < totalSteps) showStep(currentStep + 1);
      });
    }

    if (backBtn) {
      backBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (currentStep > 1) showStep(currentStep - 1);
      });
    }

    if (submitBtn) {
      submitBtn.addEventListener('click', function (e) {
        if (!validateStep(currentStep)) {
          e.preventDefault();
        }
      });
    }

    var pwInput = form.querySelector('input[name="password"]');
    var reqLengthText = document.getElementById('reqLengthText');
    var reqLengthIcon = document.getElementById('reqLength');
    if (pwInput && reqLengthText) {
      pwInput.addEventListener('input', function () {
        var ok = pwInput.value.length >= 8;
        reqLengthText.textContent = ok ? '✓ At least 8 characters' : 'At least 8 characters';
        reqLengthText.style.color = ok ? '#22c55e' : '';
        if (reqLengthIcon) reqLengthIcon.style.color = ok ? '#22c55e' : '';
      });
    }

    var contactInput = form.querySelector('input[name="contact_number"]');
    if (contactInput) {
      contactInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9\s\-]/g, '');
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRegisterForm);
  } else {
    initRegisterForm();
  }

})();
