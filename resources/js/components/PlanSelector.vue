<script setup lang="ts">

import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { LoaderCircle } from 'lucide-vue-next';
import { watch } from 'vue';
import Holidays from 'date-holidays';

const hd = new Holidays('CO');

// --- INTERFACES Y PROPS ---
// 1. Actualizamos la 'interface' para que conozca la nueva propiedad de imagen.
interface Plan {
    id: number;
    name: string;
    description: string;
    image_url: string | null; // <-- Propiedad nueva
    calculation_type: string;
    fixed_percentage: number | null;
    percentages: number[] | null;
}

const props = defineProps<{
    plans: Plan[];
    totalAvailable: number;
}>();

// --- BREADCRUMBS ---
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Selección de Plan',
        href: route('plan.selection'),
    },
];

const emit = defineEmits(['submit']);

// --- FORMULARIO Y ESTADO ---
// 2. El plan_id ahora empieza como nulo, porque el usuario debe seleccionar una tarjeta.
const form = useForm({
    plan_id: null as number | null,
    amount: '',
    receipt: null as File | null,
    payment_method: 'transfer'
});

watch(() => form.payment_method, (newMethod) => {
    if (newMethod === 'balance') {
        form.receipt = null;
    }
});

// --- LÓGICA DE CÁLCULO (SIN CAMBIOS) ---
// Esta lógica ya funciona perfectamente con el nuevo diseño.
const calculatedPayments = computed(() => {
    if (!form.amount || !form.plan_id) return [];

    const amount = parseFloat(form.amount);
    if (isNaN(amount) || amount <= 0) return [];

    const selectedPlan = props.plans.find(p => p.id === form.plan_id);
    if (!selectedPlan) return [];

    const payments = [];

    // 3. Nueva función auxiliar para encontrar el próximo día hábil
    const getNextBusinessDay = (date: Date): Date => {
        let currentDate = new Date(date);
        // Mientras sea fin de semana (0=Domingo, 6=Sábado) o sea festivo...
        while (currentDate.getDay() === 0 || currentDate.getDay() === 6 || hd.isHoliday(currentDate)) {
            // ...le sumamos un día y volvemos a comprobar.
            currentDate.setDate(currentDate.getDate() + 1);
        }
        return currentDate;
    };

    // 4. Nos aseguramos de que la PRIMERA fecha de pago también sea un día hábil
    let dueDate = getNextBusinessDay(new Date());

    if (selectedPlan.calculation_type === 'fixed_plus_final') {
        if (selectedPlan.fixed_percentage !== null) {
            const fixedPayment = amount * (selectedPlan.fixed_percentage / 100);

            for (let i = 1; i <= 5; i++) {
                payments.push({
                    label: `Cuota ${i}`,
                    value: fixedPayment,
                    date: dueDate.toISOString().split('T')[0]
                });
                // Sumamos 15 días y luego ajustamos a día hábil
                dueDate.setDate(dueDate.getDate() + 15);
                dueDate = getNextBusinessDay(dueDate); // <-- Lógica aplicada
            }
            const finalPayment = amount + fixedPayment;
            payments.push({
                label: `Cuota Final`,
                value: finalPayment,
                date: dueDate.toISOString().split('T')[0]
            });
        }
    } else {
        const percentages = selectedPlan.percentages || [];
        for (let i = 0; i < percentages.length; i++) {
            const p = percentages[i];
            payments.push({
                label: `Cuota ${i + 1} (${p}%)`,
                value: amount * (p / 100),
                date: dueDate.toISOString().split('T')[0]
            });
            // Sumamos 15 días y luego ajustamos a día hábil
            dueDate.setDate(dueDate.getDate() + 15);
            dueDate = getNextBusinessDay(dueDate); // <-- Lógica aplicada
        }
    }

    return payments.map(p => ({
        ...p,
        formattedValue: new Intl.NumberFormat('es-CO', {
            style: 'currency', currency: 'COP', minimumFractionDigits: 0
        }).format(p.value)
    }));
});

// --- FUNCIÓN DE ENVÍO (SIN CAMBIOS) ---
const handleSubmit = () => {
    emit('submit', form);
};

watch(() => form.amount, (newValue) => {
    // 1. Convertimos el valor (que puede ser un string) a número.
    const numValue = parseFloat(newValue);

    // 2. Si el campo está vacío o no es un número válido, salimos.
    if (newValue === '' || isNaN(numValue)) {
        return;
    }

    const min = 200000;
    const max = 5000000;
    const step = 100000;
    let correctedValue = numValue;

    // Redondeamos al múltiplo de 100.000 más cercano
    correctedValue = Math.round(numValue / step) * step;

    // Corregimos si es menor que el mínimo
    if (correctedValue < min) {
        correctedValue = min;
    }

    // Corregimos si es mayor que el máximo
    if (correctedValue > max) {
        correctedValue = max;
    }

    // Actualizamos el valor solo si es diferente
    // 3. Lo convertimos de nuevo a string para asignarlo al formulario.
    if (String(correctedValue) !== newValue) {
        form.amount = String(correctedValue);
    }
});
</script>

<template>
    <form @submit.prevent="handleSubmit" class="flex flex-col gap-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div 
                v-for="plan in plans" 
                :key="plan.id"
                @click="form.plan_id = plan.id"
                class="rounded-xl border bg-card text-card-foreground shadow transition-all duration-200 cursor-pointer"
                :class="{ 'ring-2 ring-primary border-primary': form.plan_id === plan.id }"
            >
                <img 
                    :src="plan.image_url ?? 'https://placehold.co/600x400/gray/white?text=Sin+Imagen'" 
                    alt="Imagen del Plan" 
                    class="aspect-video w-full rounded-t-xl object-cover"
                >
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
                <Input 
                    id="amount" 
                    type="number" 
                    v-model="form.amount" 
                    required 
                    placeholder="Mínimo $200.000"
                    min="200000" 
                    max="5000000" 
                    step="100000" 
                />
                <InputError :message="form.errors.amount" />
            </div>

            <div class="grid gap-2">
                <Label>Método de Pago</Label>
                <div class="flex items-center space-x-4 rounded-md border p-2 bg-background">
                    <label class="flex items-center space-x-2 cursor-pointer p-2 rounded-md flex-1 justify-center transition-colors" :class="{'bg-muted': form.payment_method === 'transfer'}">
                        <input type="radio" v-model="form.payment_method" value="transfer" class="sr-only" />
                        <span>Transferencia</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer p-2 rounded-md flex-1 justify-center transition-colors" :class="{'bg-muted': form.payment_method === 'balance'}">
                        <input type="radio" v-model="form.payment_method" value="balance" class="sr-only" />
                        <span>Usar Saldo Disponible</span>
                    </label>
                </div>
                <p v-if="form.payment_method === 'balance'" class="text-sm text-muted-foreground">
                    Saldo disponible: {{ totalAvailable }}
                </p>
                <InputError :message="form.errors.payment_method" />
            </div>

            <div v-if="form.payment_method === 'transfer'" class="grid gap-2">
                <Label for="receipt">Adjunta tu comprobante de pago</Label>
                <Input 
                    id="receipt" 
                    type="file"
                    @input="form.receipt = ($event.target as HTMLInputElement).files?.[0] ?? null"
                    class="file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-primary-foreground hover:file:bg-primary/90"
                />
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
                            <tr v-for="(payment, index) in calculatedPayments" :key="index" class="border-b last:border-none">
                                <td class="p-2 text-left font-bold">{{ payment.label }}</td>
                                <td class="p-2 text-right font-mono">{{ payment.formattedValue }}</td>
                                <td class="p-2 text-right font-mono text-muted-foreground">{{ payment.date }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <Button type="submit" class="w-full" :disabled="form.processing || !form.plan_id || !form.amount">
            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
            Crear Nueva Inversión
        </Button>
    </form>
</template>