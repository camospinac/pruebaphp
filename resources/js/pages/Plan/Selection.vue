<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Holidays from 'date-holidays';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';

const hd = new Holidays('CO');

// --- INTERFACES Y PROPS ---
interface Plan {
    id: number;
    name: string;
    description: string;
    image_url: string | null;
    fixed_percentage: number | null;
    closed_profit_percentage: number | null;
    closed_duration_days: number | null;
}
const props = defineProps<{ plans: Plan[] }>();

// --- ESTADO ---
const isModalOpen = ref(false);
const selectedPlanForModal = ref<Plan | null>(null);

// --- FORMULARIO ---
const form = useForm({
    plan_id: null as number | null,
    amount: '',
    receipt: null as File | null,
    investment_contract_type: 'abierta',
});

// --- LÓGICA DEL MODAL ---
const openPlanModal = (plan: Plan) => {
    selectedPlanForModal.value = plan;
    form.plan_id = plan.id;
    isModalOpen.value = true;
};
const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    selectedPlanForModal.value = null;
};

// --- LÓGICA DE CÁLCULO (VERSIÓN SIMPLE Y DIRECTA) ---
const calculatedPayments = computed(() => {
    if (!form.amount || !form.plan_id) return [];
    const amount = parseFloat(form.amount);
    if (isNaN(amount) || amount <= 0) return [];
    const selectedPlan = props.plans.find(p => p.id === form.plan_id);
    if (!selectedPlan) return [];

    const payments = [];
    const getNextBusinessDay = (date: Date): Date => {
        let currentDate = new Date(date);
        while (currentDate.getDay() === 0 || currentDate.getDay() === 6 || hd.isHoliday(currentDate)) {
            currentDate.setDate(currentDate.getDate() + 1);
        }
        return currentDate;
    };

    if (form.investment_contract_type === 'cerrada') {
        const percentage = selectedPlan.closed_profit_percentage ?? 40;
        const duration = selectedPlan.closed_duration_days ?? 90;

        // --- INICIA LA NUEVA FÓRMULA ---
        const baseProfit = amount * (percentage / 100);
        const totalProfit = baseProfit * 6;
        const totalPayment = amount + totalProfit;
        // --- FIN DE LA NUEVA FÓRMULA ---

        let finalDate = new Date();
        finalDate.setDate(finalDate.getDate() + duration);

        payments.push({
            label: `Pago Único a ${duration} días`,
            value: totalPayment,
            date: finalDate.toISOString().split('T')[0]
        });
    } else { // 'abierta'
        const percentage = selectedPlan.fixed_percentage ?? 15;
        const fixedPayment = amount * (percentage / 100);
        let dueDate = getNextBusinessDay(new Date());
        for (let i = 1; i <= 5; i++) {
            payments.push({ label: `Pago ${i}`, value: fixedPayment, date: dueDate.toISOString().split('T')[0] });
            dueDate.setDate(dueDate.getDate() + 15);
            dueDate = getNextBusinessDay(dueDate);
        }
        const finalPayment = amount + fixedPayment;
        payments.push({ label: `Pago Final`, value: finalPayment, date: dueDate.toISOString().split('T')[0] });
    }

    return payments.map(p => ({
        ...p,
        formattedValue: new Intl.NumberFormat('es-CO', {
            style: 'currency', currency: 'COP', minimumFractionDigits: 0
        }).format(p.value)
    }));
});

// --- FUNCIÓN DE ENVÍO Y WATCH ---
const submit = () => { form.post(route('plan.store'), { onSuccess: () => closeModal() }); };
watch(() => form.amount, (newValue) => {
    if (newValue === '' || isNaN(parseFloat(newValue))) return;
    const numValue = parseFloat(newValue);
    const min = 200000, max = 5000000, step = 100000;
    let correctedValue = Math.round(numValue / step) * step;
    if (correctedValue < min) correctedValue = min;
    if (correctedValue > max) correctedValue = max;
    if (String(correctedValue) !== newValue) {
        form.amount = String(correctedValue);
    }
});
</script>

<template>

    <Head title="Seleccionar Plan" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <AuthBase title="Selecciona tu Plan" description="Elige uno de nuestros planes para empezar."
            :show-logo="false">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="plan in plans" :key="plan.id" @click="openPlanModal(plan)"
                    class="rounded-xl border bg-card text-card-foreground shadow transition-all duration-200 cursor-pointer hover:ring-2 hover:ring-primary">
                    <img :src="plan.image_url ?? 'https://placehold.co/600x400/gray/white?text=Plan'"
                        alt="Imagen del Plan" class="aspect-video w-full rounded-t-xl object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold">{{ plan.name }}</h3>
                        <p class="mt-2 text-sm text-muted-foreground">{{ plan.description }}</p>
                    </div>
                </div>
            </div>
            <Dialog :open="isModalOpen" @update:open="closeModal">
                <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle class="text-2xl">{{ selectedPlanForModal?.name }}</DialogTitle>
                        <DialogDescription>{{ selectedPlanForModal?.description }}</DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submit" class="flex flex-col gap-6 pt-4">
                        <input type="hidden" name="investment_contract_type" :value="form.investment_contract_type">
                        <div class="grid gap-2">
                            <Label>Tipo de Contrato</Label>
                            <div class="flex items-center space-x-4 rounded-md border p-2 bg-background">
                                <label
                                    class="flex items-center space-x-2 cursor-pointer p-2 rounded-md flex-1 justify-center transition-colors"
                                    :class="{ 'bg-muted': form.investment_contract_type === 'abierta' }">
                                    <input type="radio" v-model="form.investment_contract_type" value="abierta"
                                        class="sr-only" />
                                    <span>Contrato Abierto</span>
                                </label>
                                <label
                                    class="flex items-center space-x-2 cursor-pointer p-2 rounded-md flex-1 justify-center transition-colors"
                                    :class="{ 'bg-muted': form.investment_contract_type === 'cerrado' }">
                                    <input type="radio" v-model="form.investment_contract_type" value="cerrada"
                                        class="sr-only" />
                                    <span>Contrato Cerrado</span>
                                </label>
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="amount">Monto Base</Label>
                            <Input id="amount" type="number" v-model="form.amount" required
                                placeholder="Mínimo $200.000" min="200000" max="5000000" step="100000" />
                            <InputError :message="form.errors.amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="receipt">Adjunta tu comprobante de pago</Label>
                            <Input id="receipt" type="file"
                                @input="form.receipt = ($event.target as HTMLInputElement).files?.[0] ?? null"
                                required />
                            <InputError :message="form.errors.receipt" />
                        </div>
                        <div v-if="calculatedPayments.length > 0" class="grid gap-2">
                            <Label>Vista Previa de Pagos</Label>
                            <div class="rounded-md border">
                                <table class="w-full text-sm">
                                    <thead class="border-b">
                                        <tr class="text-muted-foreground">
                                            <th class="p-2 text-left font-medium">Descripción</th>
                                            <th class="p-2 text-right font-medium">Monto</th>
                                            <th class="p-2 text-right font-medium">Fecha Pago</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(payment, index) in calculatedPayments" :key="index"
                                            class="border-b last:border-none">
                                            <td class="p-2 text-left font-bold">{{ payment.label }}</td>
                                            <td class="p-2 text-right font-mono">{{ payment.formattedValue }}</td>
                                            <td class="p-2 text-right font-mono text-muted-foreground">{{ payment.date
                                                }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <Button type="submit" class="w-full"
                            :disabled="form.processing || !form.plan_id || !form.amount">
                            Confirmar y Generar
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>
        </AuthBase>
    </AppLayout>
</template>