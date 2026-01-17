function displayMessage() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'success') {
        alert("Registration successful!");
    }
}

window.onload = displayMessage;