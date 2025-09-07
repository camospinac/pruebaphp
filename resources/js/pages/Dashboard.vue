<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PlanSelector from '@/components/PlanSelector.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';

// --- INTERFACES PARA TIPADO ---
interface Plan {
    id: number;
    name: string;
    description: string;
    image_url: string | null;
    calculation_type: string;
    fixed_percentage: number | null;
    percentages: number[] | null;
}

interface Transaction {
    id: string;
    created_at: string;
    tipo: 'abono' | 'retiro';
    monto: number;
    observacion: string;
}

interface Payment {
    id: number;
    amount: number;
    status: 'pending' | 'paid' | 'overdue' | 'reinvested' | 'accredited';
    payment_due_date: string;
}


interface Subscription {
    id: number;
    initial_investment: number;
    status: string;
    plan: Plan;
    payments: Payment[];
    contract_type: 'abierta' | 'cerrada';
}

// --- PROPS ---
const props = withDefaults(defineProps<{
    subscriptions?: Subscription[];
    plans?: Plan[];
    totalInversion?: number;
    totalUtilidad?: number;
    totalGanancia?: number;
    totalAvailable?: number;
    transactions?: Transaction[];
}>(), {
    subscriptions: () => [],
    plans: () => [],
    transactions: () => [],
    totalInversion: 0,
    totalUtilidad: 0,
    totalGanancia: 0,
    totalAvailable: 0,
});

// const activeTabSubscriptionId = ref<number | 'history' | null>('history');

// --- ESTADO LOCAL ---
const page = usePage();
const activeTabSubscriptionId = ref<number | 'history' | null>('history');
const isInvestmentModalOpen = ref(false); // Renombrado para claridad
const isWithdrawalModalOpen = ref(false);
const generatedCode = ref<string | null>(null);
const user = usePage().props.auth.user;

const withdrawalForm = useForm({ // <-- Nuevo formulario para el retiro
    amount: '',
});

const handleWithdrawalSubmit = () => {
    withdrawalForm.post(route('withdrawals.store'), {
        preserveScroll: true,
        onSuccess: (page) => {
            // Ya no necesitamos el console.log para depurar.

            // ¡Esta es la línea clave que reactivamos!
            // Revisa si el código existe en la respuesta y lo guarda en nuestra variable 'generatedCode'.
            if (page.props.flash?.withdrawal_code) {
                generatedCode.value = page.props.flash.withdrawal_code as string;
            }
        },
        onError: (errors) => {
            // Es bueno dejar esto para ver errores de validación.
            console.error('Errores de validación:', errors);
        }
    });
};
// 3. NUEVA FUNCIÓN para cerrar y limpiar el modal
const closeWithdrawalModal = () => {
    isWithdrawalModalOpen.value = false;
    withdrawalForm.reset();
    generatedCode.value = null; // Limpiamos el código para la próxima vez
};


// --- LÓGICA DE SUBMIT DEL MODAL ---
const handleNewSubscription = (formData: ReturnType<typeof useForm>) => {
    formData.post(route('subscriptions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isInvestmentModalOpen.value = false;
        },
    });
};

// --- PROPIEDADES COMPUTADAS ---
const activeSubscription = computed(() => {
    if (!activeTabSubscriptionId.value) return null;
    return props.subscriptions.find(sub => sub.id === activeTabSubscriptionId.value) ?? null;
});

// --- BREADCRUMBS Y HELPERS ---
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: route('dashboard') }];

const getDaysRemaining = (dueDateString: string) => {
    const today = new Date();
    const dueDate = new Date(dueDateString);
    today.setHours(0, 0, 0, 0);
    dueDate.setUTCHours(0, 0, 0, 0);
    const diffTime = dueDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    if (diffDays === 0) return { text: 'Vence hoy', class: 'text-yellow-500' };
    if (diffDays > 0) return { text: `Faltan ${diffDays} días`, class: 'text-green-500' };
    return { text: `Vencido hace ${Math.abs(diffDays)} días`, class: 'text-red-500' };
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(amount);
};

const copyToClipboard = () => {
    if (user?.referral_code) {
        navigator.clipboard.writeText(user.referral_code).then(() => {
            alert('¡Código copiado al portapapeles!');
        });
    }
};
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-y-auto">
<div v-if="user" class="p-6 rounded-xl border bg-card text-card-foreground">
    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
        
        <div class="flex-1 space-y-4">
            <div class="space-y-1">
                <h2 class="text-2xl font-bold tracking-tight">¡Hola de nuevo, {{ user.nombres }}!</h2>
                <div v-if="user.rank" class="flex items-center gap-2">
                    <span class="text-lg">🥉</span>
                    <span class="font-semibold text-primary text-base">{{ user.rank.name }}</span>
                </div>
                <p v-else class="text-sm text-muted-foreground">Aún no tienes un rango. ¡Invita a tus amigos!</p>
            </div>
            
            <div v-if="user.referral_code" class="flex items-center gap-3 pt-2">
                <p class="text-sm font-medium text-muted-foreground">Tu código para invitar:</p>
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-muted border cursor-pointer hover:bg-secondary" @click="copyToClipboard" title="Copiar código">
                    <span class="font-mono font-bold text-primary">{{ user.referral_code }}</span>
                    <button>
                        <Copy class="h-4 w-4 text-muted-foreground" />
                    </button>
                </div>
            </div>
        </div>

        <div v-if="user.next_rank" class="w-full md:w-1/3 space-y-2">
            <div class="flex justify-between items-center">
                <p class="text-sm font-medium text-muted-foreground">Progreso a:</p>
                <p class="text-sm font-bold text-foreground">{{ user.next_rank.name }}</p>
            </div>
            <div class="w-full bg-muted rounded-full h-2.5">
                <div class="bg-primary h-2.5 rounded-full" 
                     :style="{ width: (user.referral_count / user.next_rank.required_referrals) * 100 + '%' }">
                </div>
            </div>
            <p class="text-xs text-muted-foreground text-right">
                {{ user.referral_count }} / {{ user.next_rank.required_referrals }} referidos
            </p>
        </div>
        
    </div>
</div>

            <div v-if="subscriptions.length > 0" class="grid auto-rows-min gap-3 md:grid-cols-2 lg:grid-cols-3">
                <div
                    class="relative flex flex-col justify-center p-6 aspect-video overflow-hidden rounded-xl border bg-card text-card-foreground">
                    <h3 class="text-sm font-medium text-muted-foreground">Inversión Total</h3>
                    <p class="mt-1 text-4xl font-semibold tracking-tight">{{ formatCurrency(totalInversion) }}</p>
                </div>

                <div
                    class="relative flex flex-col justify-center p-6 aspect-video overflow-hidden rounded-xl border bg-card text-card-foreground">
                    <h3 class="text-sm font-medium text-muted-foreground">Utilidad Total</h3>
                    <p class="mt-1 text-4xl font-semibold tracking-tight text-green-500">+ {{
                        formatCurrency(totalUtilidad) }}</p>
                </div>
                <div
                    class="relative flex flex-col justify-center p-6 aspect-video overflow-hidden rounded-xl border bg-card text-card-foreground">
                    <h3 class="text-sm font-medium text-muted-foreground">Ganancia Total</h3>
                    <p class="mt-1 text-4xl font-semibold tracking-tight text-blue-500">{{ formatCurrency(totalGanancia)
                    }}</p>
                </div>
            </div>

            <!-- <div class="flex justify-center py-4 space-x-4">
                <Button @click="isInvestmentModalOpen = true" size="lg">Quiero Invertir</Button>
                <Button @click="isWithdrawalModalOpen = true" size="lg" variant="outline">Retirar Efectivo</Button>
                <Link :href="route('referrals.index')">
                <Button size="lg" variant="secondary">Mis Referidos</Button>
                </Link>
            </div> -->

            <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-y-auto">
                <div class="relative flex-1 rounded-xl border bg-card text-card-foreground p-6">
                    <div class="border-b mb-4">
                        <nav class="-mb-px flex space-x-6 overflow-x-auto">
                            <button @click="activeTabSubscriptionId = 'history'" :class="[
                                'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm',
                                activeTabSubscriptionId === 'history'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'
                            ]">
                                Historial de Transacciones
                            </button>

                            <button v-for="sub in subscriptions" :key="sub.id" @click="activeTabSubscriptionId = sub.id"
                                :class="[
                                    'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm',
                                    activeTabSubscriptionId === sub.id
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'
                                ]">
                                {{ sub.plan.name }} #{{ sub.id }}
                            </button>
                        </nav>
                    </div>

                    <div v-if="activeTabSubscriptionId === 'history'">
                        <div v-if="transactions.length === 0"
                            class="flex items-center justify-center h-[40vh] text-muted-foreground">
                            <p>Aún no tienes movimientos en tu historial.</p>
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full text-sm text-left">
                                <thead class="border-b">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 font-medium">Fecha</th>
                                        <th scope="col" class="px-4 py-3 font-medium">Tipo</th>
                                        <th scope="col" class="px-4 py-3 font-medium text-right">Monto</th>
                                        <th scope="col" class="px-4 py-3 font-medium">Observación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="tx in transactions" :key="tx.id" class="border-b">
                                        <td class="px-4 py-3 text-muted-foreground whitespace-nowrap">{{ new
                                            Date(tx.created_at).toLocaleString('es-CO') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="font-semibold"
                                                :class="tx.tipo === 'abono' ? 'text-green-500' : 'text-red-500'">
                                                {{ tx.tipo.charAt(0).toUpperCase() + tx.tipo.slice(1) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 font-mono text-right"
                                            :class="tx.tipo === 'abono' ? 'text-green-500' : 'text-red-500'">
                                            {{ tx.tipo === 'abono' ? '+' : '-' }} {{ formatCurrency(tx.monto) }}
                                        </td>
                                        <td class="px-4 py-3 text-muted-foreground">{{ tx.observacion }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-else-if="activeSubscription" class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left">
                            <thead class="border-b">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-medium"># Pago</th>
                                    <th scope="col" class="px-4 py-3 font-medium">Tipo Contrato</th>
                                    <th scope="col" class="px-4 py-3 font-medium text-right">Monto</th>
                                    <th scope="col" class="px-4 py-3 font-medium text-center">Estado</th>
                                    <th scope="col" class="px-4 py-3 font-medium">Fecha de Pago</th>
                                    <th scope="col" class="px-4 py-3 font-medium">Tiempo Restante</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="payment in activeSubscription.payments" :key="payment.id" class="border-b">
                                    <td class="px-4 py-3 font-medium text-muted-foreground">{{ payment.id }}</td>

                                    <td class="px-4 py-3 capitalize font-medium">
                                        {{ activeSubscription.contract_type }}
                                    </td>

                                    <td class="px-4 py-3 font-mono text-right">{{ formatCurrency(payment.amount) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="{
                                            'bg-yellow-100 text-yellow-800': payment.status === 'pending',
                                            // ...tus otras clases de estado
                                        }">
                                            {{ payment.status.charAt(0).toUpperCase() + payment.status.slice(1) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ payment.payment_due_date }}</td>
                                    <td class="px-4 py-3 font-semibold"
                                        :class="getDaysRemaining(payment.payment_due_date).class">
                                        {{ getDaysRemaining(payment.payment_due_date).text }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="flex items-center justify-center h-[40vh] text-muted-foreground">
                        <p v-if="subscriptions.length > 0">Selecciona un plan para ver los detalles.</p>
                        <p v-else>No tienes planes activos.</p>
                    </div>
                </div>
            </div>

            <div v-if="subscriptions.length > 0" class="grid md:grid-cols-3 gap-6 items-center">
                <div
                    class="md:col-span-1 p-6 rounded-xl border bg-card text-card-foreground h-full flex flex-col justify-center">
                    <h3 class="text-sm font-medium text-muted-foreground">Efectivo Disponible</h3>
                    <p class="mt-1 text-4xl font-semibold tracking-tight text-teal-500">{{
                        formatCurrency(totalAvailable) }}</p>
                </div>
                <div class="md:col-span-2 flex flex-col md:flex-row justify-center items-center gap-4">
                    <Button @click="isInvestmentModalOpen = true" size="lg" class="w-full md:w-auto">Quiero
                        Invertir</Button>
                    <Button @click="isWithdrawalModalOpen = true" size="lg" variant="outline"
                        class="w-full md:w-auto">Retirar Efectivo</Button>
                    <Link :href="route('referrals.index')" class="w-full md:w-auto">
                    <Button size="lg" variant="secondary" class="w-full">Mis Referidos</Button>
                    </Link>
                </div>
            </div>
        </div>


        <Dialog :open="isInvestmentModalOpen" @update:open="isInvestmentModalOpen = false">
            <DialogContent class="sm:max-w-[800px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Crear Nueva Inversión</DialogTitle>
                    <DialogDescription>
                        Selecciona un plan y define cómo quieres realizar el pago.
                    </DialogDescription>
                </DialogHeader>
                <PlanSelector :plans="plans" :total-available="totalAvailable" @submit="handleNewSubscription" />
            </DialogContent>
        </Dialog>

        <Dialog :open="isWithdrawalModalOpen" @update:open="closeWithdrawalModal">
            <DialogContent class="sm:max-w-[425px]">
                <div v-if="!generatedCode">
                    <DialogHeader>
                        <DialogTitle>Solicitar Retiro de Efectivo</DialogTitle>
                        <DialogDescription>
                            Ingresa el monto que deseas retirar. Se generará un código único.
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="handleWithdrawalSubmit" class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="withdrawal-amount">Monto a Retirar</Label>
                            <Input id="withdrawal-amount" type="number" :value="withdrawalForm.amount"
                                @input="withdrawalForm.amount = $event.target.value" required />
                            <p class="text-sm text-muted-foreground">
                                Disponible: {{ formatCurrency(totalAvailable) }}
                            </p>
                            <InputError :message="withdrawalForm.errors.amount" />
                        </div>
                        <Button type="submit" :disabled="withdrawalForm.processing">
                            Generar Código de Retiro
                        </Button>
                    </form>
                </div>

                <div v-else class="text-center py-4">
                    <DialogHeader>
                        <DialogTitle class="text-2xl">¡Código Generado con Éxito!</DialogTitle>
                    </DialogHeader>
                    <div class="my-6">
                        <p class="text-sm text-muted-foreground">Tu código único de retiro es:</p>
                        <p class="text-5xl font-bold tracking-widest bg-muted rounded-lg py-3 my-2">
                            {{ generatedCode }}
                        </p>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Toma una captura o anota este código y comunícate con un asesor para continuar con tu retiro.
                    </p>
                    <Button @click="closeWithdrawalModal" class="mt-6 w-full">
                        Finalizar
                    </Button>
                </div>

            </DialogContent>
        </Dialog>
    </AppLayout>
</template>