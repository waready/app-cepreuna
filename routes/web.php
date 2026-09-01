<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\RecursoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Configuracion\RolController;
//Panel Docente
use App\Http\Controllers\Asistencia\DocenteController;
use App\Http\Controllers\Asistencia\EstudianteController;
use App\Http\Controllers\Configuracion\PermisoController;

use App\Http\Controllers\Configuracion\UsuarioController;
// use App\Http\Controllers\Web\DocenteController;
use App\Http\Controllers\Auth\Login\LoginGoogleController;
use App\Http\Controllers\Auth\Login\LoginApoderadoController;
use App\Http\Controllers\Web\TramitePago\TramitePagoController;
use App\Http\Controllers\Web\DocenteApto\DocenteAptoController;
use App\Http\Controllers\Docente\CursosController as CursosDocente;
use App\Http\Controllers\Docente\PreguntasDemoController;

//Social Network
use App\Http\Controllers\RedSocial\PublicationController;
use App\Http\Controllers\RedSocial\CommentController;

//Nosotros
use App\Http\Controllers\NosotrosController;

// Panel Estudiante
use App\Http\Controllers\Docente\HorarioController as HorarioDocente;
use App\Http\Controllers\Estudiante\PagoController as PagoEstudiante;
use App\Http\Controllers\Estudiante\TestController as TestEstudiante;
use App\Http\Controllers\Estudiante\CursosController as CursoEstudiante;
use App\Http\Controllers\LibroReclamaciones\LibroReclamacionesController;

//Libro de Reclamaciones
use App\Http\Controllers\Docente\AsistenciaController as AsistenciaDocente;
use App\Http\Controllers\Estudiante\HorarioController as HorarioEstudiante;
use App\Http\Controllers\Estudiante\AsistenciaController as AsistenciaEstudiante;

/*
 |--------------------------------------------------------------------------
 | Web Routes
 |--------------------------------------------------------------------------
 |
 | Here is where you can register web routes for your application. These
 | routes are loaded by the RouteServiceProvider within a group which
 | contains the "web" middleware group. Now create something great!
 |
 */

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });
// Route::get('/', function () {
//     return redirect('/login');
// });
Route::get('/prueba', function () {
    $json = file_get_contents('data_puntaje.json');
    $obj = json_decode($json);
    // foreach ($obj as $key => $value) {
    //     # code...
    //     echo $value->user."</br>";
    // }
    // dd($obj);
    $key = array_search('77683195', array_column($obj, 'user'));
    if ($key != false) {
        // dd($key);
        dd($key);
    }
    else {
    }
// dd(Auth::user());
// return Inertia::render('Prueba', ["user" => Auth::user()]);
// return Inertia::render('Dashboard', ['users' => User::all()]);
});
// rutas google

Route::get('/', [LoginGoogleController::class , 'index'])->name("loginHome");
Route::get('/web/login/google', [LoginGoogleController::class , 'redirectToProvider'])->name('auth-redirect');
Route::get('/web/login/google/callback', [LoginGoogleController::class , 'handleProviderCallback']);
// Route::get('/', 'Auth\Login\LoginGoogleController@redirectToProvider');
// Route::get('web/login/google', 'Auth\Login\LoginGoogleController@redirectToProvider');
// Route::get('web/login/google/callback', 'Auth\Login\LoginGoogleController@handleProviderCallback');

// RUTAS EXTERNAS
// TramitePago
Route::prefix('tramite-pago')->group(function () {
    Route::get('/home', [TramitePagoController::class , 'index']);
    Route::post('/login', [TramitePagoController::class , 'login'])->name("tramitePago.login");
    Route::post('/docsRequeridos', [TramitePagoController::class , 'docs_Requeridos'])->name("tramitePago.docsRequeridos");
    Route::post('/subir', [TramitePagoController::class , 'subirArchivos'])->name('tramitePago.subir');
    Route::get('/get-documentos-expediente/{id}', [TramitePagoController::class , 'getDocumentosExpediente'])->name('tramitePago.get-documentos-expediente');
    Route::get('/get-mensaje/{id}', [TramitePagoController::class , 'getMensaje'])->name('tramitePago.get-mensaje');
    Route::get('/get-actualizar-documentos-expediente/{id}', [TramitePagoController::class , 'getActualizarDocumentosExpediente'])->name('tramitePago.get-actualizar-documentos-expediente');
    Route::post('/actualizar-documentos-expediente', [TramitePagoController::class , 'actualizarDocumentosExpediente'])->name('tramitePago.actualizar-documentos-expediente');
    Route::get('/show-document/{id}', [TramitePagoController::class , 'showDocument'])->name('tramitePago.show-document');
    Route::get('/get-detalles-horas/{id}', [TramitePagoController::class , 'getDetallesHoras'])->name('tramitePago.get-detalles-horas');

// Route::get('/get-tipo-documentos',[TramitePagoController::class, 'getTipoDocumentos'])->name('docente.get-tipo-documentos');
// Route::post('/formulario',[DocenteController::class, 'formulario'])->name("docente.formulario");

});

// Docente Apto
Route::prefix('docente-apto')->group(function () {
    Route::get('/home', [DocenteAptoController::class , 'index']);
    Route::post('/buscar', [DocenteAptoController::class , 'buscar'])->name("docenteApto.buscar");
});

// Docentes
// Route::prefix('docente')->group(function () {
//     Route::get('/home',[DocenteController::class, 'index']);
//     Route::post('/login',[DocenteController::class, 'login'])->name("docente.login");
//     Route::post('/subir',[DocenteController::class, 'subirArchivos'])->name('docente.subir');
//     Route::get('/get-tipo-documentos',[DocenteController::class, 'getTipoDocumentos'])->name('docente.get-tipo-documentos');
//     // Route::post('/formulario',[DocenteController::class, 'formulario'])->name("docente.formulario");

// });
// Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->name('dashboard');

Route::post('/login-singsuit', [LoginGoogleController::class , 'loginSinGsuit'])->name('login-singsuit.login');

Route::get('/login-apoderados', [LoginApoderadoController::class , 'index'])->name('login-apoderados.index');
Route::post('/login-apoderados', [LoginApoderadoController::class , 'login'])->name('login-apoderados.login');
Route::get('/get-apoderado', [LoginApoderadoController::class , 'getApoderados'])->name('get-apoderados.login');

Route::get('/lr-show-evidencia/{id}', [LibroReclamacionesController::class , 'showEvidencia'])->name('libroReclamaciones.lr-show-evidencia');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::prefix('recursos')->group(function () {
            Route::get('/get-sedes', [RecursoController::class , 'getSedes'])->name('recursos.get-sedes');
            Route::get('/get-areas', [RecursoController::class , 'getAreas'])->name('recursos.get-areas');
            Route::get('/get-turnos', [RecursoController::class , 'getTurnos'])->name('recursos.get-turnos');
            Route::get('/get-grupo-aulas-auxiliar', [RecursoController::class , 'getGrupoAulaAuxiliar'])->name('recursos.get-grupo-aulas-auxiliar');
            Route::get('/get-grupo-aulas-auxiliar-agrupado', [RecursoController::class , 'getGrupoAulaAuxiliarAgrupado'])->name('recursos.get-grupo-aulas-auxiliar-agrupado');
            Route::get('/get-carga-academica-asistencia', [RecursoController::class , 'getCargaAcademicaAsistencia'])->name('recursos.get-carga-academica-asistencia');
            Route::get('/get-sesiones', [RecursoController::class , 'getSesiones'])->name('recursos.get-sesiones');
            Route::get('/get-departamentos', [RecursoController::class , 'getDepartamentos'])->name('recursos.get-departamentos');
            Route::get('/get-provincias', [RecursoController::class , 'getProvincias'])->name('recursos.get-provincias');
            Route::get('/get-distritos', [RecursoController::class , 'getDistritos'])->name('recursos.get-distritos');
            Route::get('/get-alertNotificaciones', [RecursoController::class , 'alertNotificaciones'])->name('recursos.alert-notificaciones');
            Route::get('/get-notificaciones', [RecursoController::class , 'getNotificaciones'])->name('recursos.get-notificaciones');
            Route::get('/get-data-user', [RecursoController::class , 'getDataUser'])->name('recursos.get-data-user');
            Route::get('/get-ciclos', [RecursoController::class , 'getCiclos'])->name('recursos.get-ciclos');
        }
        );

        Route::group(['middleware' => ['permission:menu dashboard']], function () {
            Route::get('/dashboard', [DashboardController::class , 'index'])->name('dashboard');
        }
        );

        //social network
        Route::post('/crear-publicacion', [PublicationController::class , 'crearPublicacion'])->name('publicacion.crear');
        Route::get('/get-publicaciones', [PublicationController::class , 'getPublicaciones'])->name('publicacion.get-publicaciones');
        Route::post('/crear-comentario', [CommentController::class , 'crearComentario'])->name('comentario.crear');
        Route::get('/ver-publicacion/{id}', [PublicationController::class , 'verPublicacion'])->name('publicacion.ver');
        Route::get('/get-comentarios/{id}', [CommentController::class , 'getComentarios'])->name('comentario.get-comentarios');
        Route::get('/get-subcomentarios/{id}', [CommentController::class , 'getSubComentarios'])->name('comentario.get-subcomentarios');
        Route::get('/get-like/{id}', [PublicationController::class , 'getLike'])->name('publicacion.get-like');
        Route::post('/like/{id}', [PublicationController::class , 'like'])->name('publicacion.like');
        Route::post('/dislike/{id}', [PublicationController::class , 'dislike'])->name('publicacion.dislike');
        Route::get('/get-countcomentarios/{id}', [CommentController::class , 'countComentarios'])->name('comentario.get-countcomentarios');
        Route::post('/ocultar/{id}', [PublicationController::class , 'ocultar'])->name('publicacion.ocultar');

        //Nosotros
        Route::get('/get-directivos', [NosotrosController::class , 'getDirectivos'])->name('nosotros.get-directivos');
        Route::get('/get-mision-vision', [NosotrosController::class , 'getMisionVision'])->name('nosotros.get-mision-vision');
        Route::get('/get-objetivos', [NosotrosController::class , 'getObjetivos'])->name('nosotros.get-objetivos');
        Route::get('/get-historia', [NosotrosController::class , 'getHistoria'])->name('nosotros.get-historia');


        Route::group(['middleware' => ['permission:ver perfil']], function () {
            Route::get('/perfil', [PerfilController::class , 'index'])->name('perfil');
            Route::post('/actualizar-estudiante', [PerfilController::class , 'actualizarEstudiante'])->name('perfil.actualizar-estudiante');
            Route::post('/confirmar-datos', [PerfilController::class , 'confirmarDatos'])->name('perfil.confirmar-datos');
        }
        );

        Route::group(['middleware' => ['permission:menu asistencia']], function () {
            Route::prefix('asistencias')->group(function () {
                    // estudiantes
                    Route::resource('/asistencias-estudiantes', EstudianteController::class);
                    Route::post('/aperturar-asistencias', [EstudianteController::class , 'aperturarAsistencia'])->name('asistencias.aperturar-asistencias');
                    Route::get('/buscar-estudiante', [EstudianteController::class , 'buscarEstudiante'])->name('asistencias.buscar-estudiante');
                    Route::post('/guardar-asistencia', [EstudianteController::class , 'guardarAsistencia'])->name('asistencias.guardar-asistencia');
                    Route::get('/lista-asistencia', [EstudianteController::class , 'listaAsistencia'])->name('asistencias.lista-asistencia');
                    Route::post('/cerrar-asistencia', [EstudianteController::class , 'cerrarAsistencia'])->name('asistencias.cerrar-asistencia');
                    //docentes
                    Route::resource('/asistencias-docentes', DocenteController::class);
                }
                );
                Route::prefix('reportes')->group(function () {
                    Route::get('/docente-parte-pdf', [ReporteController::class , 'rpDocentePartePdf'])->name('reportes.parte-docente-pdf');
                //docentes
                }
                );
            }
            );

            Route::group(['middleware' => ['permission:menu configuracion']], function () {
            Route::prefix('configuracion')->group(function () {
                    // usuarios
                    Route::resource('/usuarios', UsuarioController::class);
                    Route::get('/usuarios-tabla', [UsuarioController::class , 'tabla'])->name('usuarios.tabla');
                    // permisos
                    Route::resource('/permisos', PermisoController::class);
                    Route::get('/permisos-tabla', [PermisoController::class , 'tabla'])->name('permisos.tabla');
                    // roles
                    Route::resource('/roles', RolController::class);
                    Route::get('/roles-tabla', [RolController::class , 'tabla'])->name('roles.tabla');
                }
                );
            }
            );

            // Panel estudiante
            Route::group(['middleware' => ['permission:panel estudiante']], function () {
            Route::prefix('estudiantes')->group(function () {
                    //Horario
                    Route::get('/horarios', [HorarioEstudiante::class , 'index'])->name('estudiantes.horarios');
                    Route::get('/get-horario', [HorarioEstudiante::class , 'getHorario'])->name('estudiantes.get-horario');
                    // asistencias
                    Route::get('/asistencias', [AsistenciaEstudiante::class , 'index'])->name('estudiantes.asistencias');
                    Route::get('/get-asistencias', [AsistenciaEstudiante::class , 'getAsistencia'])->name('estudiantes.get-asistencias');
                    Route::get('/get-rango-fechas', [AsistenciaEstudiante::class , 'rangoFechas'])->name('estudiantes.get-rango-fechas');
                    // cursos
                    Route::prefix('cursos')->group(function () {
                            Route::get('/mis-cursos', [CursoEstudiante::class , 'index'])->name('estudiantes.cursos');
                            Route::get('get-carga', [CursoEstudiante::class , 'getCarga'])->name('estudiantes.get-carga');
                            Route::get('get-criterios-docente', [CursoEstudiante::class , 'getCriteriosDocente'])->name('estudiantes.get-criterios-docente');
                            Route::post('carga-update', [CursoEstudiante::class , 'storeLink'])->name('estudiantes.store-link');
                            Route::post('calificar-docente/{id}', [CursoEstudiante::class , 'CalificarDocente'])->name('estudiantes.calificar-docente');
                            Route::post('calificar-docente-carga', [CursoEstudiante::class , 'calificacionDocentePorCarga'])->name('estudiantes.calificar-docente-carga');

                            Route::get('cuadernillos', [CursoEstudiante::class , 'indexCuadernillo'])->name('estudiantes.index-cuadernillo');
                            Route::get('get-cursos-estudiante', [CursoEstudiante::class , 'getCursosEstudiante'])->name('estudiantes.get-cursos-estudiante');
                            Route::get('get-url-cuadernillo', [CursoEstudiante::class , 'getUrlCuadernillo'])->name('estudiantes.get-url-cuadernillo');
                            Route::get('temarios', [CursoEstudiante::class , 'indexTemario'])->name('estudiantes.index-temario');
                            Route::get('get-cursos-estudiante-temario', [CursoEstudiante::class , 'getCursosEstudianteTemario'])->name('estudiantes.get-cursos-estudiante-temario');
                        }
                        );
                        // paogos
                        Route::get('/pagos', [PagoEstudiante::class , 'index'])->name('estudiantes.pagos');
                        Route::get('/test', [TestEstudiante::class , 'index'])->name('estudiantes.test');
                        Route::post('/validar-test', [TestEstudiante::class , 'store'])->name('estudiantes.test.validar');
                        Route::get('/constancia-test/{id}', [TestEstudiante::class , 'pdfConstancia'])->name('estudiantes.test.constancia');
                        Route::post('/validar-pago-cuota', [PagoEstudiante::class , 'validarPagoCuota'])->name('estudiantes.validar-pago');
                        Route::post('/registrar-pago-cuota', [PagoEstudiante::class , 'registrarPagoCuota'])->name('estudiantes.registrar-pago');
                    }
                    );
                }
                );


                //Panel Docente
                Route::group(['middleware' => ['permission:panel docente']], function () {
            Route::prefix('docentes')->group(function () {
                    Route::get('/horarios', [HorarioDocente::class , 'index'])->name('docentes.horarios');
                    Route::get('/get-horario', [HorarioDocente::class , 'getHorario'])->name('docentes.get-horario');

                    Route::prefix('recursos')->group(function () {
                            Route::get('/cursos', [CursosDocente::class , 'index'])->name('docentes.recursos.cursos');
                            Route::get('/get-carga', [CursosDocente::class , 'getCarga'])->name('docentes.recursos.get-carga');
                            Route::get('/get-estudiantes/{id}', [CursosDocente::class , 'getEstudiantes'])->name('docentes.recursos.get-estudiantes');
                            Route::post('/carga-update', [CursosDocente::class , 'storeLink'])->name('docentes.recursos.carga-update');
                            Route::get('/cuadernillos', [CursosDocente::class , 'indexCuadernillo'])->name('docentes.recursos.cuadernillos');
                            Route::get('/get-cursos-docente', [CursosDocente::class , 'getCursosDocente'])->name('docentes.recursos.get-cursos-docente');
                            Route::get('/temarios', [CursosDocente::class , 'indexTemario'])->name('docentes.recursos.temarios');
                            Route::get('/get-cursos-docente-temario', [CursosDocente::class , 'getCursosDocenteTemario'])->name('docentes.recursos.get-cursos-docente-temario');
                            Route::get('/sesion', [CursosDocente::class , 'indexSesiones'])->name('docentes.recursos.sesiones');
                            Route::get('/sesion-lista-data', [CursosDocente::class , 'listaSesion'])->name('docentes.recursos.lista-sesion');
                            Route::get('/get-cursos-carga', [CursosDocente::class , 'getCursosCarga'])->name('docentes.recursos.get-cursos-carga');
                            Route::post('/store-sesion', [CursosDocente::class , 'storeSesion'])->name('docentes.recursos.store-sesion');
                            Route::put('/update-sesion/{id}', [CursosDocente::class , 'updateSesion'])->name('docentes.recursos.update-sesion');
                            Route::get('/preguntas-demo', [PreguntasDemoController::class, 'index'])
                                ->middleware('docente.preguntas.demo')
                                ->name('docentes.recursos.preguntas-demo');
                            Route::get('/preguntas-demo/plantilla', [PreguntasDemoController::class, 'plantilla'])
                                ->middleware('docente.preguntas.demo')
                                ->name('docentes.recursos.preguntas-demo.plantilla');
                            Route::get('/preguntas-demo/{lote}/archivo', [PreguntasDemoController::class, 'download'])
                                ->middleware('docente.preguntas.demo')
                                ->name('docentes.recursos.preguntas-demo.download');
                            Route::get(
                                '/preguntas-demo/{lote}/revisiones/{revision}/archivo',
                                [PreguntasDemoController::class, 'downloadRevision']
                            )
                                ->middleware('docente.preguntas.demo')
                                ->name('docentes.recursos.preguntas-demo.download-revision');
                            Route::post('/preguntas-demo', [PreguntasDemoController::class, 'store'])
                                ->middleware('docente.preguntas.demo')
                                ->name('docentes.recursos.preguntas-demo.store');
                        //Route::put('update-sesion/{id}', 'Web\Docente\CursosController@updateSesion');
                        }
                        );

                        // asistencias
                        Route::get('/asistencias', [AsistenciaDocente::class , 'index'])->name('docentes.asistencias');
                        Route::get('/get-asistencias', [AsistenciaDocente::class , 'getAsistencia'])->name('docentes.get-asistencias');
                    }
                    );
                }
                );

                // Libro de Reclamaciones
                Route::get('/libro-reclamaciones', [LibroReclamacionesController::class , 'index'])->name('libroReclamaciones');
                Route::resource('/libroReclamaciones', LibroReclamacionesController::class);
                Route::get('/libro-reclamaciones-tabla', [LibroReclamacionesController::class , 'tabla'])->name('libroReclamaciones.tabla');
            });
