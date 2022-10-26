(function ($) {
    'use strict';

    function security_header_xhr_handle(data, is_formular = true, callback) {
        let xhr = new XMLHttpRequest();
        let formData = new FormData();
        xhr.open('POST', wp_security_obj.ajax_url, true);
        if (is_formular) {
            let input = new FormData(data);
            for (let [name, value] of input) {
                formData.append(name, value);
            }
        } else {
            for (let [name, value] of Object.entries(data)) {
                formData.append(name, value);
            }
        }
        xhr.onreadystatechange = function () {
            if (this.readyState === 4 && this.status === 200) {
                if (typeof callback === 'function') {
                    xhr.addEventListener("load", callback);
                    return false;
                }
            }
        }
        formData.append('_ajax_nonce', wp_security_obj.nonce);
        formData.append('action', 'SecurityHeaderHandle');
        xhr.send(formData);
    }

    $(document).on('submit', '.security-header-formular-data', function (event) {
        let button = event.originalEvent.submitter;
        let form = $(this).closest("form").get(0);
        let showSending = document.querySelector('.ajax-loading');
        if (form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
            if (showSending) {
                //showSending.classList.remove('d-none');
            }

            security_header_xhr_handle(form, true, security_header_formular_data_callback);
        }
        event.preventDefault();
        form.classList.add('was-validated');
        let isInvalid = $('.was-validated .form-control:invalid');
        if (button.hasAttribute('data-scroll')) {
            if (isInvalid.length) {
                warning_message('Bitte Eingaben überprüfen.');
                scrollToWrapper('.security-header-formular-data', 80);
            }
        }
    });

    function security_header_formular_data_callback() {
        let data = JSON.parse(this.responseText);
        swal_alert(data);
    }

    $(document).on('click', '.sh-plugin-action', function () {
        let type = $(this).attr('data-type');
        let formData;
        let lang = wp_security_obj.language;

        let formClass = $('.security-header-formular-data');
        let handle;
        let delPin;
        let count;
        let id;
        let msgData;
        $(this).attr('data-handle') ? handle = $(this).attr('data-handle') : handle = '';
        $(this).attr('data-id') ? id = $(this).attr('data-id') : id = '';
        let isForm = false;
        switch (type) {
            case'add-header-config':
                formData = {
                    'method': type,
                    'handle': $(this).attr('data-handle')
                }
                break;
            case'delete-security-header':
                formData = {
                    'method': type,
                    'id': $(this).attr('data-id'),
                    'handle': $(this).attr('data-handle'),
                    'btnText': lang.delete_header,
                    'html': lang.delete_header_html,
                    'title': lang.delete_header + '?'
                }
                security_header_fire_delete(formData);
                return false;
            case'load-default-security-header':
                delPin = createRandomInteger(4);
                formData = {
                    'method': type,
                    'id': $(this).attr('data-id')
                }
                msgData = {
                    'title': lang.reset_settings,
                    'msg': `<span class="swal-delete-body"><b class="text-center">${lang.reset_html}PIN: ${delPin}</b></span>`,
                    'delBtn': lang.reset_all_settings,
                    'pin': delPin
                }
                swal_validate_pin(msgData).then((result) => {
                    if (result !== undefined && result === delPin) {
                        security_header_xhr_handle(formData, false, security_header_action_callback);
                    }
                });
                return false;
        }

        if (formData) {
            security_header_xhr_handle(formData, isForm, security_header_action_callback)
        }

    });

    function security_header_action_callback() {
        let data = JSON.parse(this.responseText);
        if (data.status) {
            switch (data.type) {
                case'add-header-config':
                    let div = document.getElementById(data.handle);
                    div.insertAdjacentHTML('beforeend', data.template);
                    break;
                case'delete-security-header':
                    $('#' + data.handle + ' #header' + data.id).remove();
                    success_message(data.msg);
                    break;
                case'load-default-security-header':
                    location.reload();
                    break;
            }
        } else {
            warning_message(data.msg)
        }
    }

    /**
     * ===================================
     * =========== SWEET ALERT ===========
     * ===================================
     */
    function swal_alert(data) {
        if (data.status) {
            Swal.fire({
                position: 'top-end',
                title: data.title,
                text: data.msg,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                customClass: {
                    popup: 'swal-success-container'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            });
        } else {
            Swal.fire({
                position: 'top-end',
                title: data.title,
                text: data.msg,
                icon: 'error',
                timer: 2000,
                showConfirmButton: false,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                customClass: {
                    popup: 'swal-error-container'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            });
        }
    }

    async function swal_validate_pin(delMsg) {
        const inputOptions = new Promise((resolve) => {
            setTimeout(() => {
                resolve({
                    'delete_pin': delMsg.pin
                })
            }, 1000)
        });
        const {value: pin} = await Swal.fire({
            title: delMsg.title,
            html: delMsg.msg,
            input: 'text',
            inputPlaceholder: wp_security_obj.language.enter_pin,
            reverseButtons: true,
            inputLabel: wp_security_obj.language.enter_pin_label,
            validationMessage: wp_security_obj.language.pin_incorrect,
            confirmButtonText: delMsg.delBtn,
            showCancelButton: true,
            cancelButtonText: wp_security_obj.language.Cancel,
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            customClass: {
                popup: 'swal-delete-container'
            },
            inputOptions: inputOptions,
            inputValidator: (value) => {
                return new Promise((resolve) => {
                    if (value === delMsg.pin) {
                        resolve()
                    } else {
                        resolve(wp_security_obj.language.pin_incorrect)
                    }
                });
            }
        });
        if (pin) {
            return pin;
        }
    }

    function security_header_fire_delete(data) {
        Swal.fire({
            title: data.title,
            reverseButtons: true,
            html: data.html,
            confirmButtonText: data.btnText,
            cancelButtonText: wp_security_obj.language.Cancel,
            showClass: {
                //popup: 'animate__animated animate__fadeInDown'
            },
            customClass: {
                popup: 'swal-delete-container'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                if (data.sendForm) {
                    security_header_xhr_handle(data.form, true, security_header_action_callback);
                    return false;
                }
                security_header_xhr_handle(data, false, security_header_action_callback);
            }
        });
    }

})(jQuery);
