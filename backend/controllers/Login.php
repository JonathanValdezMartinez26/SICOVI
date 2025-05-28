<?php

namespace Controllers;

use Core\Controller;
use Models\Login as LoginDao;

class Login extends Controller
{
    public function index()
    {
        $script = <<<HTML
            <script>
                const validaUsuario = (btn) => {
                    const datos = {
                        usuario: $("#usuario").val(),
                        password: $("#password").val()
                    }

                    $.ajax({
                        url: "/login/validaUsuario",
                        type: "POST",
                        data: datos,
                        success: (respuesta) => {
                            respuesta = JSON.parse(respuesta)
                            if (respuesta.success) window.location.href = respuesta.datos.url
                            else {
                                showError(respuesta.mensaje)
                                if (respuesta.error) console.log(respuesta.error)
                                btn.removeAttribute("disabled")
                            }
                        },
                        error: () => {
                            showError("Error al procesar la solicitud.")
                            btn.removeAttribute("disabled")
                        }
                    })
                }

                const showError = (error) => {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: error,
                        showConfirmButton: true,
                        timer: 3000
                    })
                }

                $(document).ready(() => {
                    const formAuthentication = document.querySelector("#formAuthentication")
                    const validacion = FormValidation.formValidation(formAuthentication, {
                        fields: {
                            usuario: {
                                validators: {
                                    notEmpty: {
                                        message: "Debe ingresar su nombre de usuario"
                                    }
                                }
                            },
                            password: {
                                validators: {
                                    notEmpty: {
                                        message: "Debe ingresar su contraseña"
                                    }
                                }
                            }
                        },
                        plugins: {
                            submitButton: new FormValidation.plugins.SubmitButton(),
                            defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
                            trigger: new FormValidation.plugins.Trigger(),
                            autoFocus: new FormValidation.plugins.AutoFocus(),
                            bootstrap5: new FormValidation.plugins.Bootstrap5({
                                rowSelector: ".form-group"
                            })
                        },
                        init: (instance) => {
                            instance.on("plugins.message.placed", (e) => {
                                if (e.element.parentElement.classList.contains("input-group")) {
                                    e.element.parentElement.insertAdjacentElement("afterend", e.messageElement)
                                }
                            })
                        }
                    })

                    $("#usuario").on("keyup", (e) => {
                        e.target.value = e.target.value.toUpperCase()
                        if (e.keyCode === 13) $("#password").focus()
                    })

                    $("#password").on("keyup", (e) => {
                        if (e.keyCode === 13) {
                            e.preventDefault()
                            $("#btnLogin").click()
                        }
                    })

                    $("#btnLogin").on("click", (e) => {
                        e.preventDefault()
                        e.target.setAttribute("disabled", true)
                        
                        validacion.validate().then((validacion) => {
                            if (validacion === "Valid") validaUsuario(e.target)
                            else e.target.removeAttribute("disabled")
                        })
                    })
                })

            </script>
        HTML;

        self::set('script', $script);
        self::render("login", true);
    }

    public function validaUsuario()
    {
        $respuesta = self::respuesta(false, 'Credenciales incorrectas.');
        $validacion = LoginDao::validaUsuario($_POST);

        if ($validacion['success'] && count($validacion['datos']) > 0) {
            $datos = $validacion['datos'];

            $_SESSION['usuario_id'] = $datos['USUARIO_ID'];
            $_SESSION['usuario_nombre'] = $datos['USUARIO_NOMBRE'];
            $_SESSION['perfil_id'] = $datos['PERFIL_ID'];
            $_SESSION['perfil_nombre'] = $datos['PERFIL_NOMBRE'];
            $_SESSION['sucursal_id'] = $datos['SUCURSAL_ID'];
            $_SESSION['sucursal_nombre'] = $datos['SUCURSAL_NOMBRE'];
            $_SESSION['login'] = true;

            $respuesta = self::respuesta(true, 'Bienvenido', [
                'url' => '/' . VISTA_DEFECTO
            ]);
        }

        echo json_encode($respuesta);
    }

    public function cerrarSesion()
    {
        unset($_SESSION);
        session_unset();
        session_destroy();
        header('Location: /' . LOGIN);
        exit;
    }
}
