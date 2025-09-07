<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

import { LoaderCircle } from 'lucide-vue-next';
import Holidays from 'date-holidays';

const hd = new Holidays('CO');

// --- INTERFACES Y PROPS ---
interface Plan {
    id: number;
    name: string;
    description: string;
    image_url: string | null;
    investment_type: 'abierta' | 'cerrada';
    calculation_type: string;
    fixed_percentage: number | null;
    closed_profit_percentage: number | null;
    closed_duration_days: number | null;
    percentages: number[] | null;
}
const props = defineProps<{
    plans: Plan[];
    totalAvailable: number;
}>();

const emit = defineEmits(['submit']);

// --- FORMULARIO ---
const form = useForm({
    plan_id: null as number | null,
    amount: '',
    receipt: null as File | null,
    payment_method: 'transfer',
    investment_contract_type: 'abierta', // <-- Campo nuevo añadido
});

// --- WATCHERS ---
watch(() => form.payment_method, (newMethod) => {
    if (newMethod === 'balance') {
        form.receipt = null;
    }
});
watch(() => form.amount, (newValue) => {
    // ... tu lógica de corrección de monto
});

// --- LÓGICA DE CÁLCULO ---
const calculatedPayments = computed(() => {
    if (!form.amount || !form.plan_id) return [];
    const amount = parseFloat(form.amount);
    if (isNaN(amount) || amount <= 0) return [];
    const selectedPlan = props.plans.find(p => p.id === form.plan_id);
    if (!selectedPlan) return [];

    const payments = [];
    
    // Si el contrato es CERRADO, se ejecuta esta lógica
    if (form.investment_contract_type === 'cerrada') {

        
           const percentage = selectedPlan.closed_profit_percentage ?? 40;
    const duration = selectedPlan.closed_duration_days ?? 90;

    // --- FÓRMULA CORREGIDA ---
    const baseProfit = amount * (percentage / 100);
    const totalProfit = baseProfit * 6; // Se multiplica por 6
    const totalPayment = amount + totalProfit; // Se suma el total de la utilidad
    // --- FIN DE LA FÓRMULA ---

    let finalDate = new Date();
    finalDate.setDate(finalDate.getDate() + duration);

    payments.push({
        label: `Pago Único a ${duration} días`,
        value: totalPayment,
        date: finalDate.toISOString().split('T')[0]
    });
    } 
    // Si es ABIERTO, se ejecuta esta otra lógica
    else {
        const getNextBusinessDay = (date: Date): Date => {
            let currentDate = new Date(date);
            while (currentDate.getDay() === 0 || currentDate.getDay() === 6 || hd.isHoliday(currentDate)) {
                currentDate.setDate(currentDate.getDate() + 1);
            }
            return currentDate;
        };
        let dueDate = getNextBusinessDay(new Date());

        if (selectedPlan.calculation_type === 'fixed_plus_final' && selectedPlan.fixed_percentage) {
            const fixedPayment = amount * (selectedPlan.fixed_percentage / 100);
            for (let i = 1; i <= 5; i++) {
                payments.push({ label: `Pago ${i}`, value: fixedPayment, date: dueDate.toISOString().split('T')[0] });
                dueDate.setDate(dueDate.getDate() + 15);
                dueDate = getNextBusinessDay(dueDate);
            }
            const finalPayment = amount + fixedPayment;
            payments.push({ label: `Pago Final`, value: finalPayment, date: dueDate.toISOString().split('T')[0] });
        }
        else if (selectedPlan.calculation_type === 'equal_installments' && selectedPlan.fixed_percentage) {
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

    return payments.map(p => ({
        ...p,
        formattedValue: new Intl.NumberFormat('es-CO', {
            style: 'currency', currency: 'COP', minimumFractionDigits: 0
        }).format(p.value)
    }));
});

// --- FUNCIÓN DE ENVÍO ---
const handleSubmit = () => {
    emit('submit', form);
};
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
                <Label>Tipo de Contrato</Label>
                <div class="flex items-center space-x-4 rounded-md border p-2 bg-background">
                    <label class="flex items-center space-x-2 cursor-pointer p-2 rounded-md flex-1 justify-center transition-colors" :class="{'bg-muted': form.investment_contract_type === 'abierta'}">
                        <input type="radio" v-model="form.investment_contract_type" value="abierta" class="sr-only" />
                        <span>Contrato Abierto</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer p-2 rounded-md flex-1 justify-center transition-colors" :class="{'bg-muted': form.investment_contract_type === 'cerrada'}">
                        <input type="radio" v-model="form.investment_contract_type" value="cerrada" class="sr-only" />
                        <span>Contrato Cerrado</span>
                    </label>
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
                    Saldo disponible: {{ formatCurrency(totalAvailable) }}
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