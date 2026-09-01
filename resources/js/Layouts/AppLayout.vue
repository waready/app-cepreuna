<template>
    <!-- <div> -->
    <!-- <jet-banner /> -->

    <!-- </div -->
    <div :class="containerClass" @click="onWrapperClick">
        <!-- <app-top-bar @menu-toggle="onMenuToggle" class="hidden md:flex" /> -->
        <app-top-bar v-if="!isMobileViewport" @menu-toggle="onMenuToggle" />
        <app-top-bar-mobile v-else-if="mode == 2" :title="title" @menu-toggle="onMenuToggle" />
        <app-top-bar-social v-else @menu-toggle="onMenuToggle" />

        <transition name="layout-sidebar">
            <div :class="sidebarClass" @click="onSidebarClick" v-show="isSidebarVisible()">
                <app-profile />
                <app-menu :model="menu" @menuitem-click="onMenuItemClick" />
            </div>
        </transition>
        <div v-if="mobileMenuActive" class="layout-mask" aria-hidden="true" @click.stop="closeMobileMenu"></div>

        <div class="layout-main">
            <!-- Page Content -->
            <main>
                <slot></slot>
            </main>
        </div>

        <app-footer />
    </div>
</template>

<script>
// import JetApplicationMark from "@/Jetstream/ApplicationMark";
// import JetBanner from "@/Jetstream/Banner";
// import JetDropdown from "@/Jetstream/Dropdown";
// import JetDropdownLink from "@/Jetstream/DropdownLink";
// import JetNavLink from "@/Jetstream/NavLink";
// import JetResponsiveNavLink from "@/Jetstream/ResponsiveNavLink";

import AppTopBar from "../Sigma/AppTopbar.vue";
import AppTopBarSocial from "../Sigma/AppTopbarSocial.vue";
import AppTopBarMobile from "../Sigma/AppTopbarMobile.vue";
import AppProfile from "../Sigma/AppProfile.vue";
import AppMenu from "../Sigma/AppMenu.vue";
import AppConfig from "../Sigma/AppConfig.vue";
import AppFooter from "../Sigma/AppFooter.vue";

export default {
    components: {
        AppTopBar,
        AppProfile,
        AppMenu,
        AppConfig,
        AppFooter,
        AppTopBarSocial,
        AppTopBarMobile,
    },
    props: {
        mode: Number,
        title: String,
    },
    data() {
        return {
            isMobileViewport: window.innerWidth <= 1024,
            showingNavigationDropdown: false,
            layoutMode: "static",
            layoutColorMode: "light",
            staticMenuInactive: false,
            overlayMenuActive: false,
            mobileMenuActive: false,
            menu: [
                // rutas administracion
                {
                    label: "Inicio",
                    path: "dashboard",
                    icon: "pi pi-fw pi-home",
                    to: "dashboard",
                    sizeIcon: "18px",
                    permission: "menu dashboard",
                },
                {
                    label: "Asistencia",
                    path: "asistencias",
                    icon: "pi pi-clock",
                    sizeIcon: "16px",
                    permission: "menu asistencia",
                    items: [
                        {
                            label: "Estudiantes",
                            path: "asistencias-estudiantes",
                            icon: "pi pi-fw pi-circle-off",
                            sizeIcon: "7px",
                            to: "asistencias-estudiantes.index",
                            permission: "menu asistencia estudiante",
                        },
                        {
                            label: "Docentes",
                            path: "asistencias-docentes",
                            icon: "pi pi-fw pi-circle-off",
                            sizeIcon: "7px",
                            to: "asistencias-docentes.index",
                            permission: "menu asistencia docente",
                        },
                    ],
                },
                {
                    label: "Administracion",
                    path: "administracion",
                    icon: "pi pi-table",
                    sizeIcon: "16px",
                    permission: "menu administracion",
                    items: [],
                },
                {
                    label: "Configuracion",
                    path: "configuracion",
                    icon: "pi pi-cog",
                    sizeIcon: "18px",
                    permission: "menu configuracion",
                    items: [
                        {
                            label: "Permisos",
                            path: "permisos",
                            icon: "pi pi-fw pi-circle-off",
                            sizeIcon: "7px",
                            to: "permisos.index",
                            permission: "menu configuracion",
                        },
                        {
                            label: "Roles",
                            path: "roles",
                            icon: "pi pi-fw pi-circle-off",
                            sizeIcon: "7px",
                            to: "roles.index",
                            permission: "menu configuracion",
                        },
                        {
                            label: "Usuarios",
                            path: "usuarios",
                            icon: "pi pi-fw pi-circle-off",
                            sizeIcon: "7px",
                            to: "usuarios.index",
                            permission: "menu configuracion",
                        },
                    ],
                },
                // ----------panel estudiante----------
                // **************************************
                // {
                //     label: "Inicio",
                //     path: "inicio-estudiantes",
                //     icon: "pi pi-fw pi-home",
                //     to: "inicio-estudiantes",
                //     sizeIcon: "18px",
                //     permission: "panel estudiante",
                // },
                {
                    label: "Horarios",
                    path: "horarios",
                    icon: "pi pi-fw pi-th-large",
                    to: "estudiantes.horarios",
                    sizeIcon: "18px",
                    permission: "panel estudiante",
                },
                {
                    label: "Cursos",
                    path: "cursos",
                    icon: "pi pi-book",
                    sizeIcon: "18px",
                    permission: "panel estudiante",
                    items: [
                        {
                            label: "Mis Cursos",
                            path: "mis-cursos",
                            icon: "pi pi-fw pi-circle-off",
                            sizeIcon: "7px",
                            to: "estudiantes.cursos",
                            permission: "panel estudiante",
                        },
                        {
                            label: "Guias de Aprendizaje",
                            path: "cuadernillos",
                            icon: "pi pi-fw pi-circle-off",
                            sizeIcon: "7px",
                            to: "estudiantes.index-cuadernillo",
                            permission: "panel estudiante",
                        },
                        {
                            label: "Temarios",
                            path: "temarios",
                            icon: "pi pi-fw pi-circle-off",
                            sizeIcon: "7px",
                            to: "estudiantes.index-temario",
                            permission: "panel estudiante",
                        },
                    ],
                },
                {
                    label: "Asistencia",
                    path: "asistencias",
                    icon: "pi pi-fw pi-clock",
                    to: "estudiantes.asistencias",
                    sizeIcon: "18px",
                    permission: "panel estudiante",
                },
                {
                    label: "Pagos",
                    path: "pagos",
                    icon: "pi pi-fw pi-money-bill",
                    to: "estudiantes.pagos",
                    sizeIcon: "18px",
                    permission: "panel estudiante",
                },
                {
                    label: "Libro de Reclamaciones",
                    path: "libro-reclamaciones",
                    icon: "pi pi-fw pi-book",
                    to: "libroReclamaciones",
                    sizeIcon: "18px",
                    permission: "menu libro reclamaciones",
                },
                {
                    label: "Test Vocacional",
                    path: "Test",
                    icon: "pi pi-fw pi-file-o",
                    to: "estudiantes.test",
                    sizeIcon: "18px",
                    permission: "panel estudiante",
                },

                // ----------panel docente----------
                // **************************************
                {
                    label: "Horarios",
                    path: "horarios",
                    icon: "pi pi-fw pi-th-large",
                    to: "docentes.horarios",
                    sizeIcon: "18px",
                    permission: "panel docente",
                },
                {
                    label: "Recursos",
                    path: "recursos",
                    icon: "pi pi-book",
                    sizeIcon: "18px",
                    permission: "panel docente",
                    items: [
                        {
                            label: "Cursos",
                            path: "cursos",
                            icon: "pi pi-fw pi-circle-off",
                            sizeIcon: "7px",
                            to: "docentes.recursos.cursos",
                            permission: "panel docente",
                        },
                        {
                            label: "Guias de Aprendizaje",
                            path: "cuadernillos",
                            icon: "pi pi-fw pi-circle-off",
                            sizeIcon: "7px",
                            to: "docentes.recursos.cuadernillos",
                            permission: "panel docente",
                        },
                        {
                            label: "Temarios",
                            path: "temarios",
                            icon: "pi pi-fw pi-circle-off",
                            sizeIcon: "7px",
                            to: "docentes.recursos.temarios",
                            permission: "panel docente",
                        },
                        {
                            label: "Sesiones",
                            path: "seseiones",
                            icon: "pi pi-fw pi-circle-off",
                            sizeIcon: "7px",
                            to: "docentes.recursos.sesiones",
                            permission: "panel docente",
                        },
                    ],
                },
                {
                    label: "Preguntas",
                    path: "preguntas-demo",
                    icon: "pi pi-fw pi-question-circle",
                    sizeIcon: "18px",
                    to: "docentes.recursos.preguntas-demo",
                    permission: "panel docente",
                    visible: () => this.$page.props.features?.docente_preguntas_demo === true,
                },
                {
                    label: "Asistencia",
                    path: "asistencias",
                    icon: "pi pi-fw pi-clock",
                    to: "docentes.asistencias",
                    sizeIcon: "18px",
                    permission: "panel docente",
                },
            ],
        };
    },
    watch: {
        $route() {
            this.menuActive = false;
            this.$toast.removeAllGroups();
        },
        mobileMenuActive(active) {
            if (active) this.addClass(document.body, "body-overflow-hidden");
            else this.removeClass(document.body, "body-overflow-hidden");
        },
    },

    methods: {
        switchToTeam(team) {
            this.$inertia.put(
                route("current-team.update"),
                {
                    team_id: team.id,
                },
                {
                    preserveState: false,
                }
            );
        },

        logout() {
            this.$inertia.post(route("logout"));
        },

        onWrapperClick() {
            if (!this.menuClick) {
                this.overlayMenuActive = false;
                this.mobileMenuActive = false;
            }

            this.menuClick = false;
        },
        onMenuToggle(event) {
            this.menuClick = true;

            if (this.isDesktop()) {
                if (this.layoutMode === "overlay") {
                    if (this.mobileMenuActive === true) {
                        this.overlayMenuActive = true;
                    }

                    this.overlayMenuActive = !this.overlayMenuActive;
                    this.mobileMenuActive = false;
                } else if (this.layoutMode === "static") {
                    this.staticMenuInactive = !this.staticMenuInactive;
                }
            } else {
                this.mobileMenuActive = !this.mobileMenuActive;
            }

            event?.preventDefault();
        },
        closeMobileMenu() {
            this.mobileMenuActive = false;
            this.menuClick = false;
        },
        onSidebarClick() {
            this.menuClick = true;
        },
        onMenuItemClick(event) {
            if (event.item && !event.item.items) {
                this.overlayMenuActive = false;
                this.mobileMenuActive = false;
            }
        },
        onLayoutChange(layoutMode) {
            this.layoutMode = layoutMode;
        },
        onLayoutColorChange(layoutColorMode) {
            this.layoutColorMode = layoutColorMode;
        },
        addClass(element, className) {
            if (element.classList) element.classList.add(className);
            else element.className += " " + className;
        },
        removeClass(element, className) {
            if (element.classList) element.classList.remove(className);
            else element.className = element.className.replace(new RegExp("(^|\\b)" + className.split(" ").join("|") + "(\\b|$)", "gi"), " ");
        },
        isDesktop() {
            return !this.isMobileViewport;
        },
        isMobile() {
            return this.isMobileViewport;
        },
        syncViewport() {
            const wasMobile = this.isMobileViewport;
            this.isMobileViewport = window.innerWidth <= 1024;

            if (wasMobile && !this.isMobileViewport) {
                this.closeMobileMenu();
            }
        },
        isSidebarVisible() {
            if (this.isDesktop()) {
                if (this.layoutMode === "static") return !this.staticMenuInactive;
                else if (this.layoutMode === "overlay") return this.overlayMenuActive;
                else return true;
            } else {
                return true;
            }
        },
        changeToSpanish() {
            this.$primevue.config.locale.startsWith = "Inicia con";
            this.$primevue.config.locale.contains = "Contiene";
            this.$primevue.config.locale.notContains = "No contiene";
            this.$primevue.config.locale.endsWith = "Termina con";
            this.$primevue.config.locale.equals = "Igual";
            this.$primevue.config.locale.notEquals = "Diferente";
            this.$primevue.config.locale.noFilter = "Quitar filtro";
            this.$primevue.config.locale.lt = "Less than";
            this.$primevue.config.locale.lte = "Less than or equal to";
            this.$primevue.config.locale.gt = "Greater than";
            this.$primevue.config.locale.gte = "Greater than or equal to";
            this.$primevue.config.locale.dateIs = "Date is";
            this.$primevue.config.locale.dateIsNot = "Date is not";
            this.$primevue.config.locale.dateBefore = "Date is before";
            this.$primevue.config.locale.dateAfter = "Date is after";
            this.$primevue.config.locale.clear = "Clear";
            this.$primevue.config.locale.apply = "Apply";
            this.$primevue.config.locale.matchAll = "Match All";
            this.$primevue.config.locale.matchAny = "Match Any";
            this.$primevue.config.locale.addRule = "Add Rule";
            this.$primevue.config.locale.removeRule = "Remove Rule";
            this.$primevue.config.locale.accept = "Yes";
            this.$primevue.config.locale.reject = "No";
            this.$primevue.config.locale.choose = "Choose";
            this.$primevue.config.locale.upload = "Upload";
            this.$primevue.config.locale.cancel = "Cancel";
            this.$primevue.config.locale.dayNames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
            this.$primevue.config.locale.dayNamesShort = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
            this.$primevue.config.locale.dayNamesMin = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
            this.$primevue.config.locale.monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            this.$primevue.config.locale.monthNamesShort = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            this.$primevue.config.locale.today = "Today";
            this.$primevue.config.locale.weekHeader = "Wk";
            this.$primevue.config.locale.firstDayOfWeek = 0;
            this.$primevue.config.locale.dateFormat = "mm/dd/yy";
            this.$primevue.config.locale.weak = "Débil";
            this.$primevue.config.locale.medium = "Medio";
            this.$primevue.config.locale.strong = "Fuerte";
            this.$primevue.config.locale.passwordPrompt = "Ingrese una contraseña";
            this.$primevue.config.locale.emptyFilterMessage = "No results found";
            this.$primevue.config.locale.emptyMessage = "No available options";
        },
    },
    computed: {
        containerClass() {
            return [
                "layout-wrapper",
                {
                    "layout-overlay": this.layoutMode === "overlay",
                    "layout-static": this.layoutMode === "static",
                    "layout-static-sidebar-inactive": this.staticMenuInactive && this.layoutMode === "static",
                    "layout-overlay-sidebar-active": this.overlayMenuActive && this.layoutMode === "overlay",
                    "layout-mobile-sidebar-active": this.mobileMenuActive,
                    "p-input-filled": this.$appState.inputStyle === "filled",
                    "p-ripple-disabled": this.$primevue.config.ripple === false,
                },
            ];
        },
        sidebarClass() {
            return [
                "layout-sidebar",
                {
                    "layout-sidebar-dark": this.layoutColorMode === "dark",
                    "layout-sidebar-light": this.layoutColorMode === "light",
                },
            ];
        },
        logo() {
            return this.layoutColorMode === "dark" ? "/assets/layout/images/logo-white.svg" : "/assets/layout/images/logo.svg";
        },
    },
    mounted() {
        this.changeToSpanish();
        this.syncViewport();
        window.addEventListener("resize", this.syncViewport, { passive: true });
        // console.log(route().current());
        // console.log(route())
    },
    beforeUnmount() {
        window.removeEventListener("resize", this.syncViewport);
        this.removeClass(document.body, "body-overflow-hidden");
    },
};
</script>
