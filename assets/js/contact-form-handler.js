/**
 * Contact Form AJAX Handler with Google reCAPTCHA v2
 */

document.addEventListener("DOMContentLoaded", function () {
  const contactForm = document.getElementById("contact-form");

  if (contactForm) {
    contactForm.addEventListener("submit", handleFormSubmit);
  }
});

function handleFormSubmit(e) {
  e.preventDefault();

  // Get form data
  const form = e.target;
  const name = document.getElementById("name").value.trim();
  const email = document.getElementById("email").value.trim();
  const phone = document.getElementById("phone").value.trim();
  const subject = document.getElementById("subject").value.trim();
  const message = document.getElementById("message").value.trim();

  // Basic validation
  if (!validateForm(name, email, phone, message)) {
    return;
  }

  // Get reCAPTCHA token
  grecaptcha
    .execute("6LdvyIQsAAAAAGPo_FR2mENa_xYM_iZnoCIPf5J7", { action: "submit" })
    .then(function (token) {
      sendFormData(form, token);
    });
}

function validateForm(name, email, phone, message) {
  // Check required fields
  if (!name || !email || !phone || !message) {
    showNotification("Please fill in all required fields.", "error");
    return false;
  }

  // Validate email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    showNotification("Please enter a valid email address.", "error");
    return false;
  }

  // Validate phone (at least 7 characters)
  if (phone.length < 7) {
    showNotification("Please enter a valid phone number.", "error");
    return false;
  }

  // Validate message length
  if (message.length < 10) {
    showNotification("Message must be at least 10 characters long.", "error");
    return false;
  }

  return true;
}

function sendFormData(form, recaptchaToken) {
  const submitBtn = form.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;

  // Show loading state
  submitBtn.disabled = true;
  submitBtn.textContent = "Sending...";
  submitBtn.classList.add("loading");

  // Prepare form data
  const formData = new FormData(form);
  formData.append("recaptcha_token", recaptchaToken);

  // Send AJAX request
  fetch(form.action, {
    method: "POST",
    body: formData,
    headers: {
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        showNotification(data.message, "success");
        form.reset();
        // Reset reCAPTCHA
        if (typeof grecaptcha !== "undefined") {
          grecaptcha.reset();
        }
      } else {
        showNotification(
          data.message || "An error occurred. Please try again.",
          "error",
        );
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification(
        "An error occurred while sending your message. Please try again.",
        "error",
      );
    })
    .finally(() => {
      // Restore button state
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
      submitBtn.classList.remove("loading");
    });
}

function showNotification(message, type) {
  // Remove existing notification
  const existingNotification = document.querySelector(".form-notification");
  if (existingNotification) {
    existingNotification.remove();
  }

  // Create notification element
  const notification = document.createElement("div");
  notification.className = `form-notification form-notification-${type}`;
  notification.textContent = message;
  notification.setAttribute("role", "alert");

  // Add to form
  const form = document.getElementById("contact-form");
  form.insertAdjacentElement("afterend", notification);

  // Auto remove after 5 seconds
  setTimeout(() => {
    notification.remove();
  }, 5000);
}

// Add CSS for notifications dynamically
if (!document.getElementById("form-notification-styles")) {
  const style = document.createElement("style");
  style.id = "form-notification-styles";
  style.textContent = `
        .form-notification {
            margin: 20px 0;
            padding: 15px 20px;
            border-radius: 5px;
            font-size: 14px;
            animation: slideIn 0.3s ease-out;
        }
        
        .form-notification-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .form-notification-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        button[type="submit"].loading {
            opacity: 0.7;
        }
    `;
  document.head.appendChild(style);
}
