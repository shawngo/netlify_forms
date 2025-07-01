/**
 * @file
 * Webhook administration functionality.
 */

(function ($, Drupal, once) {
  "use strict";

  /**
   * Copy webhook URL to clipboard.
   */
  Drupal.behaviors.webhookAdmin = {
    attach: function (context, settings) {
      once("webhook-copy", ".copy-webhook-url", context).forEach(function (
        element
      ) {
        $(element).on("click", function (e) {
          e.preventDefault();

          var button = $(this);
          var url = button.data("webhook-url");

          // Use the modern clipboard API
          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard
              .writeText(url)
              .then(function () {
                showToast("Webhook URL copied!", "success");
                button.text("Copied!");
                setTimeout(function () {
                  button.text("📋 Copy");
                }, 2000);
              })
              .catch(function (err) {
                console.error("Could not copy text: ", err);
                fallbackCopyTextToClipboard(url, button);
              });
          } else {
            // Fallback for older browsers
            fallbackCopyTextToClipboard(url, button);
          }
        });
      });
    },
  };

  /**
   * Show toast notification.
   */
  function showToast(message, type) {
    var toast = $(
      '<div class="webhook-toast webhook-toast--' +
        type +
        '">' +
        message +
        "</div>"
    );

    // Add to body (starts off-screen due to CSS)
    $("body").append(toast);

    // Force a reflow so the CSS is applied before animation
    toast[0].offsetHeight;

    // Trigger animation on next frame
    requestAnimationFrame(function () {
      toast.addClass("webhook-toast--show");
    });

    // Remove after 3 seconds
    setTimeout(function () {
      toast.removeClass("webhook-toast--show");
      setTimeout(function () {
        toast.remove();
      }, 300);
    }, 3000);
  }

  /**
   * Fallback copy method for older browsers.
   */
  function fallbackCopyTextToClipboard(text, button) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
      var successful = document.execCommand("copy");
      if (successful) {
        showToast("Webhook URL copied!", "success");
        button.text("Copied!");
        setTimeout(function () {
          button.text("📋 Copy");
        }, 2000);
      } else {
        showToast("Copy failed", "error");
        button.text("Copy failed");
        setTimeout(function () {
          button.text("📋 Copy");
        }, 2000);
      }
    } catch (err) {
      showToast("Copy not supported", "error");
      button.text("Copy not supported");
      setTimeout(function () {
        button.text("📋 Copy");
      }, 2000);
    }

    document.body.removeChild(textArea);
  }
})(jQuery, Drupal, once);
