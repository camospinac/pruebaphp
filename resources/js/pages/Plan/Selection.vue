<script setup lang="ts">
import { computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { LoaderCircle } from 'lucide-vue-next';
//import InputError from '@/components/ui/input-error'; // <-- CORRECCIÓN: La ruta correcta
import Holidays from 'date-holidays';

const hd = new Holidays('CO');

// --- INTERFACES Y PROPS (Sin cambios) ---
interface Plan {
    id: number;
    name: string;
    description: string;
    image_url: string | null;
    calculation_type: string;
    fixed_percentage: number | null;
    percentages: number[] | null;
}

const props = defineProps<{
    plans: Plan[];
}>();

// --- BREADCRUMBS (Sin cambios) ---
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Selección de Plan',
        href: route('plan.selection'),
    },
];

// --- FORMULARIO Y ESTADO (Sin cambios) ---
const form = useForm({
    plan_id: null as number | null,
    amount: '',
    receipt: null as File | null,
});

// --- LÓGICA DE CÁLCULO (Con corrección) ---
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
    let dueDate = getNextBusinessDay(new Date());

    if (selectedPlan.calculation_type === 'fixed_plus_final') {
        if (selectedPlan.fixed_percentage !== null) {
            const fixedPayment = amount * (selectedPlan.fixed_percentage / 100);
            for (let i = 1; i <= 5; i++) {
                payments.push({ label: `Pago ${i}`, value: fixedPayment, date: dueDate.toISOString().split('T')[0] });
                dueDate.setDate(dueDate.getDate() + 15);
                dueDate = getNextBusinessDay(dueDate);
            }
            const finalPayment = amount + fixedPayment;
            payments.push({ label: `Pago Final`, value: finalPayment, date: dueDate.toISOString().split('T')[0] });
        }
    } else if (selectedPlan.calculation_type === 'equal_installments') {
        if (selectedPlan.fixed_percentage !== null) {
            const fixedPayment = amount * (selectedPlan.fixed_percentage / 100);
            const totalProfit = fixedPayment * 6;
            const totalToPay = amount + totalProfit;
            const installment = totalToPay / 6;
            for (let i = 1; i <= 6; i++) {
                payments.push({ label: `Pago ${i} de 6`, value: installment, date: dueDate.toISOString().split('T')[0] });
                dueDate.setDate(dueDate.getDate() + 15);
                dueDate = getNextBusinessDay(dueDate);
            }
        }
    } 
    // --- CORRECCIÓN: FALTABA ESTE BLOQUE 'ELSE' ---
    else { 
        // Lógica para planes basados en un array de porcentajes
        const percentages = selectedPlan.percentages || [];
        for (let i = 0; i < percentages.length; i++) {
            const p = percentages[i];
            payments.push({
                label: `Cuota ${i + 1} (${p}%)`,
                value: amount * (p / 100),
                date: dueDate.toISOString().split('T')[0]
            });
            dueDate.setDate(dueDate.getDate() + 15);
            dueDate = getNextBusinessDay(dueDate);
        }
    }

    return payments.map(p => ({
        ...p,
        formattedValue: new Intl.NumberFormat('es-CO', {
            style: 'currency', currency: 'COP', minimumFractionDigits: 0
        }).format(p.value)
    }));
});

// --- FUNCIÓN DE ENVÍO Y WATCH (Sin cambios) ---
const submit = () => {
    form.post(route('plan.store'));
};

watch(() => form.amount, (newValue) => {
    // ... tu lógica de corrección de monto ...
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">

        <Head title="Seleccionar Plan" />

        <AuthBase title="Selecciona tu Plan" description="Elige uno de nuestros planes para empezar."
            :show-logo="false">
            <form @submit.prevent="submit" class="flex flex-col gap-8">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="plan in plans" :key="plan.id" @click="form.plan_id = plan.id"
                        class="rounded-xl border bg-card text-card-foreground shadow transition-all duration-200 cursor-pointer"
                        :class="{ 'ring-2 ring-primary border-primary': form.plan_id === plan.id }">
                        <img :src="plan.image_url ?? 'https://placehold.co/600x400/gray/white?text=Sin+Imagen'"
                            alt="Imagen del Plan" class="aspect-video w-full rounded-t-xl object-cover">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold tracking-tight">{{ plan.name }}</h3>
                            <p class="mt-2 text-sm text-muted-foreground">{{ plan.description }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="form.plan_id" class="grid gap-6 animate-in fade-in-50">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <span class="w-full border-t"></span>
                        </div>
                        <div class="relative flex justify-center text-xs uppercase">
                            <span class="bg-background px-2 text-muted-foreground">Define tu inversión</span>
                        </div>
                    </div>

                    <div class="grid gap-2">

                        <Label for="amount">Monto Base</Label>
                        <Input id="amount" type="number" v-model="form.amount" required placeholder="Mínimo $200.000"
                            min="200000" max="5000000" step="100000" />

                        <InputError :message="form.errors.amount" />
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
                                        <td class="p-2 text-right font-mono text-muted-foreground">{{ payment.date }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="receipt">Adjunta tu comprobante de pago</Label>
                    <Input id="receipt" type="file"
                        @input="form.receipt = ($event.target as HTMLInputElement).files?.[0] ?? null"
                        class="file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-primary-foreground hover:file:bg-primary/90"
                        required />
                    <InputError :message="form.errors.receipt" />
                </div>

                <Button type="submit" class="w-full" :disabled="form.processing || !form.plan_id || !form.amount">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Confirmar y Generar
                </Button>
            </form>
        </AuthBase>
    </AppLayout>
</template>