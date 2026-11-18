// Password strength meter (register page)
var pw = document.getElementById('password');
if (pw) {
  pw.addEventListener('input', function () {
    var v = pw.value, s = 0;
    if (v.length >= 10) s++;
    if (/[a-z]/.test(v)) s++;
    if (/[A-Z]/.test(v)) s++;
    if (/\d/.test(v)) s++;
    if (/[^\w\s]/.test(v)) s++;
    var m = document.getElementById('meter');
    m.textContent = ['Very weak', 'Very weak', 'Weak', 'Fair', 'Good', 'Strong'][s];
    m.className = 'meter m' + s;
  });
}
// Dashboard tabs
document.querySelectorAll('.tab').forEach(function (b) {
  b.addEventListener('click', function () {
    document.querySelectorAll('.page').forEach(function (p) { p.classList.add('hide'); });
    document.getElementById(b.dataset.tab).classList.remove('hide');
  });
});
