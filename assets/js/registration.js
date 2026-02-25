document.addEventListener('DOMContentLoaded', function() {
    const provinceSelect = document.getElementById('province');
    const districtSelect = document.getElementById('district');
    const constituencySelect = document.getElementById('constituency');
    
    // Load districts when province changes
    provinceSelect.addEventListener('change', function() {
        const provinceId = this.value;
        
        // Reset and disable dependent dropdowns
        districtSelect.innerHTML = '<option value="">Select District</option>';
        constituencySelect.innerHTML = '<option value="">Select Constituency</option>';
        districtSelect.disabled = true;
        constituencySelect.disabled = true;
        
        if (provinceId) {
            fetch(`../api/get_districts.php?province_id=${provinceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        data.districts.forEach(district => {
                            const option = document.createElement('option');
                            option.value = district.id;
                            option.textContent = district.name;
                            districtSelect.appendChild(option);
                        });
                        districtSelect.disabled = false;
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    });
    
    // Load constituencies when district changes
    districtSelect.addEventListener('change', function() {
        const districtId = this.value;
        
        // Reset and disable constituency dropdown
        constituencySelect.innerHTML = '<option value="">Select Constituency</option>';
        constituencySelect.disabled = true;
        
        if (districtId) {
            fetch(`../api/get_constituencies.php?district_id=${districtId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        data.constituencies.forEach(constituency => {
                            const option = document.createElement('option');
                            option.value = constituency.id;
                            option.textContent = `Constituency ${constituency.constituency_number}`;
                            constituencySelect.appendChild(option);
                        });
                        constituencySelect.disabled = false;
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    });
    
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    
    function checkPasswordStrength() {
        const password = passwordInput.value;
        const strength = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /\d/.test(password)
        };
        
        const strengthCount = Object.values(strength).filter(Boolean).length;
        const strengthBar = document.querySelector('.password-strength');
        
        if (strengthBar) {
            strengthBar.className = 'password-strength';
            if (strengthCount === 4) {
                strengthBar.classList.add('strong');
            } else if (strengthCount >= 2) {
                strengthBar.classList.add('medium');
            } else if (strengthCount > 0) {
                strengthBar.classList.add('weak');
            }
        }
    }
    
    if (passwordInput) {
        passwordInput.addEventListener('input', checkPasswordStrength);
    }
    
    // Confirm password match
    if (confirmInput) {
        confirmInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Preview profile photo
    const photoInput = document.getElementById('profile_photo');
    if (photoInput) {
        photoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.querySelector('.photo-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.className = 'photo-preview';
                        this.parentNode.appendChild(preview);
                    }
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }.bind(this);
                reader.readAsDataURL(file);
            }
        });
    }
});