document.addEventListener("DOMContentLoaded", function () {
    (function ($) {
        'use strict';
        let colImportSettings = $('#colImportSettings');
        let oAuthContainer = $('#colOauthSettings');

        function fetch_ig_admin_ajax_handle(data, is_formular = true, callback) {
            let formData = new FormData();
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
            formData.append('_ajax_nonce', instagram_ajax_obj.nonce);
            formData.append('action', 'InstagramImporter');

            fetch(instagram_ajax_obj.ajax_url, {
                method: 'POST',
                body: formData
            }).then((response) => response.json())
                .then((result) => {
                    if (typeof callback === 'function') {
                        document.addEventListener("load", callback(result));
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
        }

        if(colImportSettings.length !== 0){
            let formData = {
                'method':'plugin_settings'
            }
            fetch_ig_admin_ajax_handle(formData, false, load_settings_callback)
            function load_settings_callback(data){
                if(data.status){
                    colImportSettings.html(data.template);
                    let endtime = new Date(data.next_time);
                    initializeClock('#nextSyncTime', endtime);
                } else {
                    warning_message(data.msg)
                }
            }
        }

        $(document).on('dblclick', '.dblclick', function () {
            $(this).removeAttr('readonly')
        });

        $(document).on('click', '.sync-instagram-media', function () {
            let formData = {
                'method': 'sync_instagram_media'
            }
            swal_timer();
            fetch_ig_admin_ajax_handle(formData, false, sync_instagram_media_callback)
        });

        function sync_instagram_media_callback(data){
            Swal.close();
            if(data.status){

            }
            swal_alert_response(data)
        }

        $(document).on('submit', '.igi-submit-admin-form', function (event) {
            let button = event.originalEvent.submitter;
            let formData = $(this).closest("form").get(0);
            fetch_ig_admin_ajax_handle(formData, true, igi_submit_form_callback)
            event.preventDefault();
        });

        function igi_submit_form_callback(data){
            swal_alert_response(data)
        }


        $(document).on('click', '.btn-toggle', function () {
            $('.btn-toggle').prop('disabled', false);
            $(this).prop('disabled', true);
            let parent = $(this).attr('data-parent');
            let target = $(this).attr('data-target');
            let type = $(this).attr('data-type');
            let formData;
            switch (type) {
                case 'app_help':
                    return false;
                case 'plugin_settings':
                case 'import_handle':
                case 'oauth_settings':
                    formData = {
                        'method': type,
                        'target': target,
                        'parent': parent,
                        'handle': 'insert'
                    };
                    break;
            }
            if (formData) {
                fetch_ig_admin_ajax_handle(formData, false, btn_toggle_callback)
            } else {
                new bootstrap.Collapse(target, {
                    toggle: true,
                    parent: parent
                })
            }
        });


        function btn_toggle_callback(data){
            switch (data.type){
                case'plugin_settings':
                    if(data.status){
                        colImportSettings.html(data.template);
                        let endtime = new Date(data.next_time);
                        initializeClock('#nextSyncTime', endtime);
                        new bootstrap.Collapse(data.target, {
                            toggle: true,
                            parent: data.parent
                        })
                    } else {
                        warning_message(data.msg)
                    }
                    break;
                case 'oauth_settings':
                    if(data.status){
                        oAuthContainer.html(data.template);
                        new bootstrap.Collapse(data.target, {
                            toggle: true,
                            parent: data.parent
                        })
                    } else {
                        warning_message(data.msg)
                    }
                    break;
            }

        }


        let instagramImporterSendFormTimeout;
        $(document).on('input propertychange change', '.instagram-autosafe-form', function () {
            let formData = $(this).closest("form").get(0);
            let target = $(this).attr('data-target');
            let spin = $(target);
            spin.html('');
            spin.addClass('wait');
            clearTimeout(instagramImporterSendFormTimeout);
            instagramImporterSendFormTimeout = setTimeout(function () {
                fetch_ig_admin_ajax_handle(formData, true, instagram_importer_formular_autosave_callback);
            }, 1000);
        });

        function instagram_importer_formular_autosave_callback(data){
            let btnSync = $('#btnSync');
            switch (data.type) {
                case 'oauth_import_settings_handle':
                    //$('#oAuthCallbackUrl').attr('href', data.callback_url)
                    if(data.show_btn && data.status){
                        btnSync.removeClass('d-none')
                    } else {
                        btnSync.addClass('d-none')
                    }
                    show_ajax_spinner(data, '.oauth');
                    break;
            }
        }


        //Message Handle
        function success_message(msg) {
            let x = document.getElementById("snackbar-success");
            x.innerHTML = msg;
            x.className = "show";
            setTimeout(function () {
                x.className = x.className.replace("show", "");
            }, 3000);
        }

        function warning_message(msg) {
            let x = document.getElementById("snackbar-warning");
            x.innerHTML = msg;
            x.className = "show";
            setTimeout(function () {
                x.className = x.className.replace("show", "");
            }, 3000);
        }

        function show_ajax_spinner(data, target = '') {
            let msg = '';
            if (data.status) {
                msg = '<i class="text-success fw-bold bi bi-check2-circle"></i>&nbsp; Saved! Last: ' + data.msg;
            } else {
                msg = '<i class="text-danger bi bi-exclamation-triangle"></i>&nbsp; ' + data.msg;
            }
            let spinner = document.querySelector(target + '.ajax-status-spinner');
            spinner.classList.remove('wait');
            spinner.innerHTML = msg;
        }

        function getTimeRemaining(endtime) {
            const total = Date.parse(endtime) - Date.parse(new Date());
            const seconds = Math.floor((total / 1000) % 60);
            const minutes = Math.floor((total / 1000 / 60) % 60);
            const hours = Math.floor((total / (1000 * 60 * 60)) % 24);
            const days = Math.floor(total / (1000 * 60 * 60 * 24));

            return {
                total,
                days,
                hours,
                minutes,
                seconds
            };
        }

        function initializeClock(target, endtime) {

            if (!target) {
                return false;
            }
            const timeinterval = setInterval(() => {
                const t = getTimeRemaining(endtime);
                const clock = document.querySelector(target);
                if(!clock){
                    return false;
                }
                clock.innerHTML = `<small class="fw-semibold mt-2">${t.days > 0 ? t.days + ' Tag(e) ' : '0 Tag(e) '} ${('0' + t.hours).slice(-2)}:${('0' + t.minutes).slice(-2)}:${('0' + t.seconds).slice(-2)}</small>`;
                if (t.total <= 0) {
                    clearInterval(timeinterval);
                }
            }, 1000);
        }

        function swal_alert_response(data) {
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
                        popup: 'bg-light'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutUp'
                    }
                }).then();
            } else {
                Swal.fire({
                    position: 'center',
                    title: data.title,
                    text: data.msg,
                    icon: 'error',
                    timer: 3000,
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
                }).then();
            }
        }

        function swal_timer(data = '') {
            let timerInterval
            Swal.fire({
                title: data.title ? data.title : 'Synchronisieren ',
                html: data.msg ? data.msg : 'Instagram Beiträge werden Synchronisiert...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                customClass: {
                    popup: 'swal-info-container'
                },
                hideClass: {
                    //popup: 'animate__animated animate__fadeOutUp'
                },
                didOpen: () => {
                    Swal.showLoading()
                    /*const b = Swal.getHtmlContainer().querySelector('b')
                    timerInterval = setInterval(() => {
                        b.textContent = Swal.getTimerLeft()
                    }, 100)*/
                },
                willClose: () => {
                    clearInterval(timerInterval)
                }
            }).then((result) => {
            })
        }

    })(jQuery);
});
