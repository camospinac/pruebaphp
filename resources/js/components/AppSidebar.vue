<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
// 👇 Importa el helper `usePage` y los nuevos iconos
import { Head, Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Users, ArrowLeftRight } from 'lucide-vue-next'; // <-- Nuevos iconos
import AppLogo from './AppLogo.vue';

// 1. Obtenemos los datos de la página, que incluyen al usuario autenticado
const page = usePage();
const user = page.props.auth.user;

// 2. Creamos el array base con los items que todos pueden ver
const mainNavItems: NavItem[] = [
    {
        title: 'Inicio',
        href: route('dashboard'), // Usamos el helper route()
        icon: LayoutGrid,
        // active: route().current('dashboard'),
    },
];

// 3. Si el usuario es admin, añadimos los enlaces de administración
if (user.rol === 'admin') {
    mainNavItems.push(
        {
            title: 'Usuarios',
            href: route('admin.users.index'),
            icon: Users,
            //active: route().current('admin.users.index'),
        },
        {
            title: 'Transacciones',
            href: route('admin.transactions.index'),
            icon: ArrowLeftRight,
            //active: route().current('admin.transactions.index'),
        }
    );
}

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                        <div class="flex items-center gap-2">
                            <!-- Logo -->
                            <div class="mb-1 flex h-9 w-9 items-center justify-center rounded-md overflow-hidden">
                                <img src="/img/icons/icon-72x72.png" alt="Mi Logo"
                                    class="w-full h-full object-contain" />
                            </div>

                            <!-- Texto -->
                            <span class="text-base font-medium text-foreground">
                                Finance PWA
                            </span>
                        </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <div class="flex min-h-screen flex-col">
        <slot />
    </div>
</template>