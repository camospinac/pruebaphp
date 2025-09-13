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
import { PlusCircle, MinusCircle } from 'lucide-vue-next';
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

const formatCurrency = (amount: number | string) => {
    const number = Number(amount);
    if (isNaN(number)) return '';
    return new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP', minimumFractionDigits: 0
    }).format(number);
};

const formattedAmount = computed(() => {
    if (form.amount === '') return '';
    return formatCurrency(form.amount);
});

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
        // --- INICIA LA DEPURACIÓN ---
        console.clear(); // Limpia la consola para ver solo este cálculo
        console.log("--- MODO CERRADO ACTIVADO ---");

        const percentage = selectedPlan.closed_profit_percentage ?? 50;
        const duration = selectedPlan.closed_duration_days ?? 90;
        console.log(`Monto de entrada: ${amount}, Porcentaje a usar: ${percentage}`);

        const baseProfit = amount * (percentage / 100);
        console.log(`Ganancia Base (Monto * %): ${baseProfit}`);

        const totalProfit = baseProfit * 3;
        console.log(`Ganancia Total (Ganancia Base * 3): ${totalProfit}`);

        const totalPayment = amount + totalProfit;
        console.log(`==> PAGO FINAL CALCULADO: ${totalPayment}`);
        // --- FIN DE LA DEPURACIÓN ---

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
// watch(() => form.amount, (newValue) => {
//     if (newValue === '' || isNaN(parseFloat(newValue))) return;
//     const numValue = parseFloat(newValue);
//     const min = 200000, max = 5000000, step = 100000;
//     let correctedValue = Math.round(numValue / step) * step;
//     if (correctedValue < min) correctedValue = min;
//     if (correctedValue > max) correctedValue = max;
//     if (String(correctedValue) !== newValue) {
//         form.amount = String(correctedValue);
//     }
// });

const increaseAmount = () => {
    const min = 200000;
    const max = 5000000;
    const step = 100000;

    let currentValue = parseFloat(form.amount) || 0;

    if (currentValue < min) {
        currentValue = min;
    } else if (currentValue < max) {
        currentValue += step;
    }

    form.amount = String(currentValue);
};

const decreaseAmount = () => {
    const min = 200000;
    const step = 100000;

    let currentValue = parseFloat(form.amount);

    if (currentValue > min) {
        currentValue -= step;
    }

    form.amount = String(currentValue);
};
</script>

<template>

    <Head title="Seleccionar Plan" />
    <AppLayout>
        <AuthBase title="Selecciona tu Plan" description="Elige uno de nuestros planes para empezar."
            :show-logo="false">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
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
                        <DialogDescription class="text-base">{{ selectedPlanForModal?.description }}</DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submit" class="flex flex-col gap-6 pt-4">
                        <div class="relative">
        <!-- <div class="absolute inset-0 flex items-center">
            <span class="w-full border-t"></span>
        </div> -->
        <div class="relative flex justify-center text-xs uppercase">
            <Label>¿No sabes qué contrato escoger?</Label>
            <!-- <span class="bg-card px-2 text-muted-foreground"></span> -->
        </div>
    </div>

    <details class="group border rounded-lg overflow-hidden">
        <summary class="flex items-center justify-between p-4 cursor-pointer hover:bg-muted">
            <div class="flex items-center gap-3">
                <span class="text-xl">🕊️</span>
                <h4 class="font-semibold">Contrato Abierto</h4>
            </div>
            <span class="transition-transform duration-300 group-open:rotate-180">▼</span>
        </summary>
        <div class="p-4 border-t text-sm text-muted-foreground space-y-3">
            <p><strong>Mayor control, liquidez y libertad.</strong></p>
            <p>Ideal para quienes desean controlar de cerca sus ingresos y mantener flexibilidad a lo largo del tiempo.</p>
            <div class="p-3 bg-muted/50 rounded-md border">
                <p><strong>Ejemplo de aplicación:</strong><br>
                ☕ Doña Rosa, 52 años, vendedora de tintos en Girardot. Con esfuerzo salió adelante y hoy invierte en un Contrato Abierto que le da liquidez y ganancias cada 15 días. Así vive más holgada y con nuevas oportunidades para su negocio y su familia.</p>
            </div>
        </div>
    </details>

    <details class="group border rounded-lg overflow-hidden">
        <summary class="flex items-center justify-between p-4 cursor-pointer hover:bg-muted">
            <div class="flex items-center gap-3">
                <span class="text-xl">🔒</span>
                <h4 class="font-semibold">Contrato Cerrado</h4>
            </div>
            <span class="transition-transform duration-300 group-open:rotate-180">▼</span>
        </summary>
        <div class="p-4 border-t text-sm text-muted-foreground space-y-3">
            <p><strong>Mayor rendimiento, concentración y a largo plazo.</strong></p>
            <p>Ideal para quienes buscan maximizar resultados sin necesidad de retiros mensuales.</p>
            <div class="p-3 bg-muted/50 rounded-md border">
                <p><strong>Ejemplo de aplicación:</strong><br>
                📱 Julián, 38 años, microempresario en Girardot. Empezó arreglando celulares con lo justo, pero nunca dejó de soñar. Hoy invierte en un contrato cerrado de 3 meses que le da rentabilidad y el impulso para cumplir metas y seguir creciendo.</p>
            </div>
        </div>
    </details>
                        <input type="hidden" name="investment_contract_type" :value="form.investment_contract_type">
                        <div class="grid gap-2">
                            <Label>Tipo de Contrato</Label>
                            <div class="flex items-center space-x-4 rounded-md border p-2 bg-background">
    <label 
        class="flex items-center space-x-2 cursor-pointer p-2 rounded-md flex-1 justify-center transition-all duration-200" 
        :class="{
            'bg-muted': form.investment_contract_type === 'abierta',
            'opacity-60': form.investment_contract_type !== 'abierta'
        }"
    >
        <input type="radio" v-model="form.investment_contract_type" value="abierta" class="sr-only" />
        <span>Abierto</span>
    </label>

    <label 
        class="flex items-center space-x-2 cursor-pointer p-2 rounded-md flex-1 justify-center transition-all duration-200" 
        :class="{
            'bg-muted': form.investment_contract_type === 'cerrada',
            'opacity-60': form.investment_contract_type !== 'cerrada'
        }"
    >
        <input type="radio" v-model="form.investment_contract_type" value="cerrada" class="sr-only" />
        <span>Cerrado</span>
    </label>
</div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="amount">Monto Base</Label>
                            <div class="relative">
                                <Input id="amount" type="text" :value="formattedAmount" required v-model="form.amount"
                                    placeholder="Usa +/- para ajustar" readonly
                                    class="pr-20 text-center font-bold text-lg" />
                                <div class="absolute inset-y-0 right-0 flex items-center">
                                    <Button type="button" @click="decreaseAmount" variant="ghost" size="icon"
                                        class="h-full rounded-none [touch-action:manipulation]"
                                        :disabled="parseFloat(form.amount) <= 200000">
                                        <MinusCircle class="h-6 w-6" />
                                    </Button>
                                    <Button type="button" @click="increaseAmount" variant="ghost" size="icon"
                                        class="h-full rounded-l-none [touch-action:manipulation]"
                                        :disabled="parseFloat(form.amount) >= 5000000">
                                        <PlusCircle class="h-6 w-6" />
                                    </Button>
                                </div>
                            </div>
                            <InputError :message="form.errors.amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="receipt">Adjunta tu comprobante de pago</Label>
                            <Input id="receipt" type="file"
                                @input="form.receipt = ($event.target as HTMLInputElement).files?.[0] ?? null"
                                accept="image/*" required />
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