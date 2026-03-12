$(function () {
  // Get the form.
  var form = $("#contact-form");

  // Get the messages div or create one if it doesn't exist
  var formMessages = $(".ajax-response");
  if (formMessages.length === 0) {
    form.prepend('<div class="ajax-response"></div>');
    formMessages = $(".ajax-response");
  }

  // Clear previous errors function
  function clearErrors() {
    formMessages.removeClass("error success").text("").hide();
    form.find(".rs-contact-input-box").removeClass("has-error");
    form.find(".field-error").remove();
  }

  // Function to show field errors
  function showFieldErrors(errors) {
    for (var field in errors) {
      if (errors.hasOwnProperty(field)) {
        var input = form.find('[name="' + field + '"]');
        if (input.length) {
          input.closest(".rs-contact-input-box").addClass("has-error");
          input.after(
            '<span class="field-error" style="color: #e74c3c; font-size: 12px; display: block; margin-top: 5px;">' +
              errors[field] +
              "</span>",
          );
        }
      }
    }
  }

  // Function to execute reCAPTCHA callback for v3
  window.onRecaptchaSuccess = function (token) {
    // Store the token in a hidden field if it exists
    var tokenField = form.find('input[name="g-recaptcha-response"]');
    if (tokenField.length === 0) {
      form.append(
        '<input type="hidden" name="g-recaptcha-response" value="' +
          token +
          '">',
      );
    } else {
      tokenField.val(token);
    }
  };

  // Set up an event listener for the contact form.
  form.on("submit", function (e) {
    // Stop the browser from submitting the form.
    e.preventDefault();

    // Clear previous messages and errors
    clearErrors();

    // Disable submit button to prevent double submission
    var submitBtn = form.find('button[type="submit"]');
    var originalBtnText = submitBtn.text();
    submitBtn.prop("disabled", true).text("Sending...");

    // Check if reCAPTCHA v2 is present
    if (
      typeof grecaptcha !== "undefined" &&
      document.querySelector(
        '.g-recaptcha[data-callback!="onRecaptchaSuccess"]',
      )
    ) {
      // For reCAPTCHA v2 Checkbox
      var recaptchaToken = grecaptcha.getResponse();
      if (!recaptchaToken) {
        clearErrors();
        formMessages
          .addClass("error")
          .text("Please complete the reCAPTCHA verification")
          .show();
        submitBtn.prop("disabled", false).text(originalBtnText);
        return false;
      }
    }

    // Serialize the form data.
    var formData = form.serialize();

    // Submit the form using AJAX.
    $.ajax({
      type: "POST",
      url: form.attr("action"),
      data: formData,
      dataType: "json",
      timeout: 10000,
    })
      .done(function (response) {
        // Re-enable submit button
        submitBtn.prop("disabled", false).text(originalBtnText);

        if (response.success) {
          // Show success message
          formMessages
            .removeClass("error")
            .addClass("success")
            .text(response.message)
            .show();

          // Clear the form
          form[0].reset();

          // Clear reCAPTCHA if present
          if (typeof grecaptcha !== "undefined") {
            grecaptcha.reset();
          }

          // Scroll to message
          $("html, body").animate(
            {
              scrollTop: formMessages.offset().top - 100,
            },
            800,
          );

          // Optional: Hide success message after 5 seconds
          setTimeout(function () {
            formMessages.fadeOut();
          }, 5000);
        } else {
          // Show error message
          formMessages
            .removeClass("success")
            .addClass("error")
            .text(response.message)
            .show();

          // Show field-specific errors if present
          if (response.errors && Object.keys(response.errors).length > 0) {
            showFieldErrors(response.errors);
          }

          // Reset reCAPTCHA if present
          if (typeof grecaptcha !== "undefined") {
            grecaptcha.reset();
          }

          // Scroll to message
          $("html, body").animate(
            {
              scrollTop: formMessages.offset().top - 100,
            },
            800,
          );
        }
      })
      .fail(function (jqXHR, textStatus, errorThrown) {
        // Re-enable submit button
        submitBtn.prop("disabled", false).text(originalBtnText);

        // Make sure that the formMessages div has the 'error' class.
        formMessages.removeClass("success").addClass("error").show();

        // Set the message text based on error type
        var errorMessage =
          "An error occurred while processing your request. Please try again.";
        if (textStatus === "timeout") {
          errorMessage =
            "Request timeout. Please check your connection and try again.";
        } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
          errorMessage = jqXHR.responseJSON.message;
        } else if (jqXHR.responseText) {
          errorMessage = jqXHR.responseText;
        }

        formMessages.text(errorMessage);

        // Reset reCAPTCHA if present
        if (typeof grecaptcha !== "undefined") {
          grecaptcha.reset();
        }

        // Scroll to message
        $("html, body").animate(
          {
            scrollTop: formMessages.offset().top - 100,
          },
          800,
        );
      });
  });
});
