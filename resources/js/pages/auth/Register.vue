<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const showReferralInput = ref(false);
const form = useForm({
    nombres: '',
    apellidos: '',
    celular: '',
    email: '',
    password: '',
    password_confirmation: '',
    referral_code: '',
});


const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>


<template>
    <AuthBase title="Registrate ahora" description="Ingresa los datos para crear tu cuenta.">

        <Head title="Registro" />


        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="nombres">Nombres</Label>
                    <Input id="nombres" type="text" required autofocus :tabindex="1" autocomplete="given-name"
                        v-model="form.nombres" placeholder="Nombres" />
                    <InputError :message="form.errors.nombres" />
                </div>


                <div class="grid gap-2">
                    <Label for="apellidos">Apellidos</Label>
                    <Input id="apellidos" type="text" required :tabindex="2" autocomplete="family-name"
                        v-model="form.apellidos" placeholder="Apellidos" />
                    <InputError :message="form.errors.apellidos" />
                </div>


                <div class="grid gap-2">
                    <Label for="celular">Celular</Label>
                    <Input id="celular" type="text" required :tabindex="3" autocomplete="tel" v-model="form.celular"
                        placeholder="Número de celular" />
                    <InputError :message="form.errors.celular" />
                </div>


                <div class="grid gap-2">
                    <Label for="email">Correo electrónico</Label>
                    <Input id="email" type="email" required :tabindex="4" autocomplete="email" v-model="form.email"
                        placeholder="email@dominio.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="has-referral" v-model="showReferralInput" />
                    <Label for="has-referral" class="text-sm font-medium">¿Te invitó un amigo? Ingresa su código</Label>
                </div>

                <div v-if="showReferralInput" class="grid gap-2">
                    <Label for="referral_code">Código de Referido</Label>
                    <Input id="referral_code" type="text" v-model="form.referral_code" placeholder="EJEMPLO-123"
                        autocomplete="off" />
                    <InputError :message="form.errors.referral_code" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Contraseña</Label>
                    <Input id="password" type="password" required :tabindex="5" autocomplete="new-password"
                        v-model="form.password" placeholder="Contraseña" />
                    <InputError :message="form.errors.password" />
                </div>


                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirmar contraseña</Label>
                    <Input id="password_confirmation" type="password" required :tabindex="6" autocomplete="new-password"
                        v-model="form.password_confirmation" placeholder="Confirmar contraseña" />
                    <InputError :message="form.errors.password_confirmation" />
                </div>


                <Button type="submit" class="mt-2 w-full" tabindex="7" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Crear cuenta
                </Button>
            </div>


            <div class="text-center text-sm text-muted-foreground">
                Ya tienes una cuenta?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="8">Ingresa</TextLink>
            </div>
        </form>
    </AuthBase>
</template>