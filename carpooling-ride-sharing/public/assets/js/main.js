// Small vanilla-JS bits only, per GROUNDRULES.md §5 (no JS framework).

document.addEventListener('DOMContentLoaded', function () {
    // Confirm before any destructive/state-changing POST marked data-confirm.
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // Live fare preview on the booking form: seats x price-per-seat, recomputed
    // client-side purely for display -- the server always recomputes and
    // enforces the real total inside the booking transaction.
    var seatsInput = document.getElementById('booking-seats');
    if (seatsInput) {
        var pricePerSeat = parseFloat(seatsInput.getAttribute('data-price') || '0');
        var maxSeats = parseInt(seatsInput.getAttribute('max') || '1', 10);
        var totalEl = document.getElementById('booking-total');
        var updateTotal = function () {
            var seats = Math.max(1, Math.min(maxSeats, parseInt(seatsInput.value || '1', 10) || 1));
            if (totalEl) {
                totalEl.textContent = '₹' + (seats * pricePerSeat).toFixed(2);
            }
        };
        seatsInput.addEventListener('input', updateTotal);
        updateTotal();
    }

    // Star rating widget: clicking a star sets the hidden "stars" value and
    // highlights every star up to it.
    document.querySelectorAll('.star-picker').forEach(function (picker) {
        var input = picker.querySelector('input[type="hidden"]');
        var stars = Array.prototype.slice.call(picker.querySelectorAll('.star-pick'));
        var paint = function (n) {
            stars.forEach(function (s, i) {
                s.textContent = (i < n) ? '★' : '☆';
            });
        };
        stars.forEach(function (s, i) {
            s.addEventListener('click', function () {
                input.value = String(i + 1);
                paint(i + 1);
            });
        });
        paint(parseInt(input.value || '0', 10));

        var form = picker.closest('form');
        if (form) {
            form.addEventListener('submit', function (e) {
                if (parseInt(input.value || '0', 10) < 1) {
                    e.preventDefault();
                    window.alert('Please pick a star rating before submitting.');
                }
            });
        }
    });
});
