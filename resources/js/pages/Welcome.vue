<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import { onMounted, ref, computed } from "vue";
import counterUp from "counterup2";

onMounted(() => {
  const counters = document.querySelectorAll < HTMLElement > ('[data-toggle="counter-up"]');

  counters.forEach((el) => {
    counterUp(el, {
      duration: 2000, // velocidad en ms
      delay: 16       // delay entre incrementos
    });
  });
});

const plans = {
    basico: {
        name: 'Plan Básico',
        percentages: [5, 10, 15, 20, 25, 30]
    },
    premium: {
        name: 'Plan Premium',
        percentages: [8, 12, 18, 22, 28, 35]
    }
};

// --- REFS PARA LOS ELEMENTOS DEL DOM ---
// Creamos "refs" que Vue conectará con los elementos del template.
const amountInput = ref<HTMLInputElement | null>(null);
const planSelect = ref<HTMLSelectElement | null>(null);
const resultsContainer = ref<HTMLDivElement | null>(null);
const simulatorForm = ref<HTMLFormElement | null>(null);

// --- ESTADOS REACTIVOS ---
// Guardamos los valores del formulario en refs para que sean reactivos.
const selectedPlanKey = ref('');
const amount = ref<number | null>(null);

// --- PROPIEDAD COMPUTADA PARA LOS CÁLCULOS ---
// Esto se recalcula automáticamente cuando amount o selectedPlanKey cambian.
const calculatedPayments = computed(() => {
    if (!amount.value || !selectedPlanKey.value) {
        return [];
    }

    const selectedPlan = plans[selectedPlanKey.value as keyof typeof plans];
    if (!selectedPlan) return [];

    return selectedPlan.percentages.map((p, index) => {
        const calculatedValue = amount.value! * (p / 100);
        const formattedValue = new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0
        }).format(calculatedValue);

        return {
            id: index,
            percentage: p,
            value: formattedValue
        };
    });
});

// --- FUNCIÓN PARA LIMPIAR ---
function clearSimulator() {
    amount.value = null;
    selectedPlanKey.value = '';
}

// --- LIFECYCLE HOOK ---
// onMounted se asegura de que este código se ejecute DESPUÉS de que el HTML exista.
onMounted(() => {
    // Prevenimos el envío tradicional del formulario si estuviera dentro de uno.
    if (simulatorForm.value) {
        simulatorForm.value.addEventListener('submit', (e) => e.preventDefault());
    }
});
</script>

<template>

  <Head>
    <meta charset="utf-8">
    <title>Startup - Finance PWA</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
  </Head>

  <!-- <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
    <div class="spinner"></div>
</div> -->
  <!-- Spinner End -->


  <!-- Topbar Start -->
  <!-- <div class="container-fluid bg-dark px-5 d-none d-lg-block">
        <div class="row gx-0">
            <div class="col-lg-8 text-center text-lg-start mb-2 mb-lg-0">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <small class="me-3 text-light"><i class="fa fa-map-marker-alt me-2"></i>123 Street, New York, USA</small>
                    <small class="me-3 text-light"><i class="fa fa-phone-alt me-2"></i>+012 345 6789</small>
                    <small class="text-light"><i class="fa fa-envelope-open me-2"></i>info@example.com</small>
                </div>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href=""><i class="fab fa-twitter fw-normal"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href=""><i class="fab fa-facebook-f fw-normal"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href=""><i class="fab fa-linkedin-in fw-normal"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href=""><i class="fab fa-instagram fw-normal"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle" href=""><i class="fab fa-youtube fw-normal"></i></a>
                </div>
            </div>
        </div>
    </div> -->
  <!-- Topbar End -->


  <!-- Navbar & Carousel Start -->
  <div class="container-fluid position-relative p-0">
    <nav class="navbar navbar-expand-lg navbar-dark px-5 py-3 py-lg-0">
      <a href="index.html" class="navbar-brand p-0">
        <h1 class="m-0"><i class="fa fa-user-tie me-2"></i>Finance WPA</h1>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
        <span class="fa fa-bars"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarCollapse">
        <div class="navbar-nav ms-auto py-0">
          <a href="#" class="nav-item nav-link active">Inicio</a>
          <a href="#" class="nav-item nav-link">Nosotros</a>
          <a href="#" class="nav-item nav-link">Proyectos</a>
        </div>
        <Link :href="route('login')" class="btn btn-primary py-2 px-4 ms-3">
    Iniciar Sesión
</Link>
      </div>
    </nav>

    <div id="header-carousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
      <div class="carousel-inner">
        <div class="carousel-item active">
          <img class="w-100" src="img/carousel-1.jpg" alt="Image">
          <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
            <div class="p-3" style="max-width: 900px;">
              <!-- <h5 class="text-white text-uppercase mb-3 animated slideInDown">Creative & Innovative</h5> -->
              <h1 class="display-1 text-white mb-md-4 animated zoomIn">Cumple tus metas invirtiendo seguro</h1>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <img class="w-100" src="img/carousel-2.jpg" alt="Image">
          <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
            <div class="p-3" style="max-width: 900px;">
              <!-- <h5 class="text-white text-uppercase mb-3 animated slideInDown">Creative & Innovative</h5> -->
              <h1 class="display-1 text-white mb-md-4 animated zoomIn">Cumple tus metas invirtiendo seguro</h1>
            </div>
          </div>
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#header-carousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Anterior</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#header-carousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Siguiente</span>
      </button>
    </div>
  </div>
  <!-- Navbar & Carousel End -->

  <!-- Facts Start -->
  <div id="facts-section" class="container-fluid facts py-5 pt-lg-0">
    <div class="container py-5 pt-lg-0">
      <div class="row gx-0">
        <div class="col-lg-4" data-wow-delay="0.1s" data-wow>
          <div class="bg-primary shadow d-flex align-items-center justify-content-center p-4" style="height: 150px;">
            <div class="bg-white d-flex align-items-center justify-content-center rounded mb-2"
              style="width: 60px; height: 60px;">
              <i class="fa fa-users text-primary"></i>
            </div>
            <div class="ps-4">
              <h5 class="text-white mb-0">Inversionistas</h5>
              <h1 class="text-white mb-0" data-toggle="counter-up">511</h1>
            </div>
          </div>
        </div>
        <div class="col-lg-4" data-wow-delay="0.3s">
          <div class="bg-light shadow d-flex align-items-center justify-content-center p-4" style="height: 150px;">
            <div class="bg-primary d-flex align-items-center justify-content-center rounded mb-2"
              style="width: 60px; height: 60px;">
              <i class="fa fa-check text-white"></i>
            </div>
            <div class="ps-4">
              <h5 class="text-primary mb-0">Proyectos</h5>
              <h1 class="mb-0" data-toggle="counter-up">143</h1>
            </div>
          </div>
        </div>
        <div class="col-lg-4" data-wow-delay="0.6s">
          <div class="bg-primary shadow d-flex align-items-center justify-content-center p-4" style="height: 150px;">
            <div class="bg-white d-flex align-items-center justify-content-center rounded mb-2"
              style="width: 60px; height: 60px;">
              <i class="fa fa-award text-primary"></i>
            </div>
            <div class="ps-4">
              <h5 class="text-white mb-0">Ganancias (En Millones)</h5>
              <h1 class="text-white mb-0" data-toggle="counter-up">950</h1>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Facts Start -->


  <!-- About Start -->
  <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
    <div class="container py-5">
      <div class="row g-5">
        <div class="col-lg-7">
          <div class="section-title position-relative pb-3 mb-5">
            <h5 class="fw-bold text-primary text-uppercase">¿Quienes somos?</h5>
            <h1 class="mb-0">Una empresa dedicada a generar rentabilidades con la inversión de capital a proyectos.</h1>
          </div>
          <p class="mb-4">Tempor erat elitr rebum at clita. Diam dolor diam ipsum et tempor sit. Aliqu diam amet diam et
            eos labore. Clita erat ipsum et lorem et sit, sed stet no labore lorem sit. Sanctus clita duo justo et
            tempor eirmod magna dolore erat amet</p>
          <div class="row g-0 mb-3">
            <div class="col-sm-6 wow zoomIn" data-wow-delay="0.2s">
              <h5 class="mb-3"><i class="fa fa-check text-primary me-3"></i>Transparencia</h5>
              <h5 class="mb-3"><i class="fa fa-check text-primary me-3"></i>Profesionales</h5>
            </div>
            <div class="col-sm-6 wow zoomIn" data-wow-delay="0.4s">
              <h5 class="mb-3"><i class="fa fa-check text-primary me-3"></i>Soporte 24/7</h5>
              <h5 class="mb-3"><i class="fa fa-check text-primary me-3"></i>Alta rentabilidad</h5>
            </div>
          </div>
          <div class="d-flex align-items-center mb-4 wow fadeIn" data-wow-delay="0.6s">
            <div class="bg-primary d-flex align-items-center justify-content-center rounded"
              style="width: 60px; height: 60px;">
              <i class="fa fa-phone-alt text-white"></i>
            </div>
            <div class="ps-4">
              <h5 class="mb-2">Habla con un asesor</h5>
              <h4 class="text-primary mb-0">+012 345 6789</h4>
            </div>
          </div>
          <!-- <a href="quote.html" class="btn btn-primary py-3 px-5 mt-3 wow zoomIn" data-wow-delay="0.9s">Request A Quote</a> -->
        </div>
        <div class="col-lg-5" style="min-height: 500px;">
          <div class="position-relative h-100">
            <img class="position-absolute w-100 h-100 rounded wow zoomIn" data-wow-delay="0.9s" src="img/about.jpg"
              style="object-fit: cover;">
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- About End -->


  <!-- Features Start -->
  <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
    <div class="container py-5">
      <div class="section-title text-center position-relative pb-3 mb-5 mx-auto" style="max-width: 600px;">
        <h5 class="fw-bold text-primary text-uppercase">¿Por qué escogernos?</h5>
        <h1 class="mb-0">Estamos para hacer crecer tu capital exponencialmente</h1>
      </div>
      <div class="row g-5">
        <div class="col-lg-4">
          <div class="row g-5">
            <div class="col-12 wow zoomIn" data-wow-delay="0.2s">
              <div class="bg-primary rounded d-flex align-items-center justify-content-center mb-3"
                style="width: 60px; height: 60px;">
                <i class="fa fa-cubes text-white"></i>
              </div>
              <h4>Las mejores industrias</h4>
              <p class="mb-0">Magna sea eos sit dolor, ipsum amet lorem diam dolor eos et diam dolor</p>
            </div>
            <div class="col-12 wow zoomIn" data-wow-delay="0.6s">
              <div class="bg-primary rounded d-flex align-items-center justify-content-center mb-3"
                style="width: 60px; height: 60px;">
                <i class="fa fa-award text-white"></i>
              </div>
              <h4>Clientes Sastifechos</h4>
              <p class="mb-0">Magna sea eos sit dolor, ipsum amet lorem diam dolor eos et diam dolor</p>
            </div>
          </div>
        </div>
        <div class="col-lg-4  wow zoomIn" data-wow-delay="0.9s" style="min-height: 350px;">
          <div class="position-relative h-100">
            <img class="position-absolute w-100 h-100 rounded wow zoomIn" data-wow-delay="0.1s" src="img/feature.jpg"
              style="object-fit: cover;">
          </div>
        </div>
        <div class="col-lg-4">
          <div class="row g-5">
            <div class="col-12 wow zoomIn" data-wow-delay="0.4s">
              <div class="bg-primary rounded d-flex align-items-center justify-content-center mb-3"
                style="width: 60px; height: 60px;">
                <i class="fa fa-users-cog text-white"></i>
              </div>
              <h4>Equipo de Profesionales</h4>
              <p class="mb-0">Magna sea eos sit dolor, ipsum amet lorem diam dolor eos et diam dolor</p>
            </div>
            <div class="col-12 wow zoomIn" data-wow-delay="0.8s">
              <div class="bg-primary rounded d-flex align-items-center justify-content-center mb-3"
                style="width: 60px; height: 60px;">
                <i class="fa fa-phone-alt text-white"></i>
              </div>
              <h4>Transparecia y Legalidad</h4>
              <p class="mb-0">Magna sea eos sit dolor, ipsum amet lorem diam dolor eos et diam dolor</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Features Start -->


  <!-- Service Start -->
  <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
    <div class="container py-5">
      <div class="section-title text-center position-relative pb-3 mb-5 mx-auto" style="max-width: 600px;">
        <h5 class="fw-bold text-primary text-uppercase">Algunos de nuestros proyectos</h5>
        <h1 class="mb-0">Alta rentabilidad y seguridad</h1>
      </div>
      <div class="row g-5">

        <div class="col-lg-4 col-md-6 wow zoomIn" data-wow-delay="0.6s">
          <div
            class="service-item bg-light rounded d-flex flex-column align-items-center justify-content-center text-center">
            <div class="service-icon">
              <i class="fa fa-chart-pie text-white"></i>
            </div>
            <h4 class="mb-3">Minería</h4>
            <p class="m-0">Amet justo dolor lorem kasd amet magna sea stet eos vero lorem ipsum dolore sed</p>
            <a class="btn btn-lg btn-primary rounded" href="">
              <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 wow zoomIn" data-wow-delay="0.9s">
          <div
            class="service-item bg-light rounded d-flex flex-column align-items-center justify-content-center text-center">
            <div class="service-icon">
              <i class="fa fa-code text-white"></i>
            </div>
            <h4 class="mb-3">Energías Renovables</h4>
            <p class="m-0">Amet justo dolor lorem kasd amet magna sea stet eos vero lorem ipsum dolore sed</p>
            <a class="btn btn-lg btn-primary rounded" href="">
              <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>
        
        
        <div class="col-lg-4 col-md-6 wow zoomIn" data-wow-delay="0.6s">
          <div
            class="service-item bg-light rounded d-flex flex-column align-items-center justify-content-center text-center">
            <div class="service-icon">
              <i class="fa fa-search text-white"></i>
            </div>
            <h4 class="mb-3">Tecnologías</h4>
            <p class="m-0">Amet justo dolor lorem kasd amet magna sea stet eos vero lorem ipsum dolore sed</p>
            <a class="btn btn-lg btn-primary rounded" href="">
              <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Service End -->


  <!-- Pricing Plan Start -->
  <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
    <div class="container py-5">
      <div class="section-title text-center position-relative pb-3 mb-5 mx-auto" style="max-width: 600px;">
        <h5 class="fw-bold text-primary text-uppercase">Planes de Inversión</h5>
        <h1 class="mb-0">2 planes de inversión de acuerdo a tus necesidades</h1>
      </div>
      <div class="row g-4 justify-content-center">
        <div class="col-lg-4 wow slideInUp" data-wow-delay="0.6s">
          <div class="bg-light rounded">
            <div class="border-bottom py-4 px-5 mb-4">
              <h4 class="text-primary mb-1">Plan Básico</h4>
              <small class="text-uppercase">Para pequeñas inversiones</small>
            </div>
            <div class="p-5 pt-0">
              <h1 class="display-5 mb-3">
                <small class="align-top" style="font-size: 22px; line-height: 45px;">Desde</small>200k<small
                  class="align-bottom" style="font-size: 16px; line-height: 40px;">/ COP</small>
              </h1>
              <div class="d-flex justify-content-between mb-3"><span>Pagos Quincenales</span><i
                  class="fa fa-check text-primary pt-1"></i></div>
              <div class="d-flex justify-content-between mb-3"><span>Garantía</span><i
                  class="fa fa-check text-primary pt-1"></i></div>
              <div class="d-flex justify-content-between mb-3"><span>Liquidez</span><i
                  class="fa fa-check text-primary pt-1"></i></div>
            </div>
          </div>
        </div>
        <div class="col-lg-4 wow slideInUp" data-wow-delay="0.9s">
          <div class="bg-light rounded">
            <div class="border-bottom py-4 px-5 mb-4">
              <h4 class="text-primary mb-1">Plan Premium</h4>
              <small class="text-uppercase">Para grandes inversiones</small>
            </div>
            <div class="p-5 pt-0">
              <h1 class="display-5 mb-3">
                <small class="align-top" style="font-size: 22px; line-height: 45px;">Desde</small>5M<small
                  class="align-bottom" style="font-size: 16px; line-height: 40px;">/ COP</small>
              </h1>
              <div class="d-flex justify-content-between mb-3"><span>Pagos Mensuales</span><i
                  class="fa fa-check text-primary pt-1"></i></div>
              <div class="d-flex justify-content-between mb-3"><span>Garantía</span><i
                  class="fa fa-check text-primary pt-1"></i></div>
              <div class="d-flex justify-content-between mb-3"><span>Liquidez</span><i
                  class="fa fa-check text-primary pt-1"></i></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Pricing Plan End -->


  <!-- Quote Start -->
  <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
    <div class="container py-5">
      <div class="row g-5">
        <div class="col-lg-7">
          <div class="section-title position-relative pb-3 mb-5">
            <h5 class="fw-bold text-primary text-uppercase">Simulador Virtual</h5>
            <h1 class="mb-0">No sabes como se moverá tu capital? Simula aquí</h1>
          </div>

          <p class="mb-4">Eirmod sed tempor lorem ut dolores. Aliquyam sit sadipscing kasd ipsum. Dolor ea et dolore et
            at sea ea at dolor, justo ipsum duo rebum sea invidunt voluptua. Eos vero eos vero ea et dolore eirmod et.
            Dolores diam duo invidunt lorem. Elitr ut dolores magna sit. Sea dolore sanctus sed et. Takimata takimata
            sanctus sed.</p>
          <div class="d-flex align-items-center mt-2 wow zoomIn" data-wow-delay="0.6s">
            <div class="bg-primary d-flex align-items-center justify-content-center rounded"
              style="width: 60px; height: 60px;">
              <i class="fa fa-phone-alt text-white"></i>
            </div>
            <div class="ps-4">
              <h5 class="mb-2">Tienes dudas? Escribenos</h5>
              <h4 class="text-primary mb-0">+012 345 6789</h4>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
        <div class="bg-primary rounded h-100 d-flex align-items-center p-5 wow zoomIn" data-wow-delay="0.9s">
            
            <form ref="simulatorForm" style="width: 100%;">
                <div class="row g-3">
                    <h4 class="text-white mb-3 text-center">Simulador Virtual</h4>

                    <div class="col-12">
                        <input
                            ref="amountInput"
                            v-model.number="amount"
                            type="number"
                            class="form-control bg-light border-0"
                            placeholder="Monto a simular (Ej: 1000000)"
                            style="height: 55px;"
                        >
                    </div>

                    <div class="col-12">
                        <select
                            ref="planSelect"
                            v-model="selectedPlanKey"
                            class="form-select bg-light border-0"
                            style="height: 55px;"
                        >
                            <option value="">Seleccione un Plan</option>
                            <option value="basico">Plan Básico</option>
                            <option value="premium">Plan Premium</option>
                        </select>
                    </div>

                    <div v-if="calculatedPayments.length > 0" class="col-12 mt-4" ref="resultsContainer">
                        <ul class="list-group">
                            <li
                                v-for="payment in calculatedPayments"
                                :key="payment.id"
                                class="list-group-item d-flex justify-content-between align-items-center bg-light border-0 text-dark mb-2 rounded"
                            >
                                <span>Cuota {{ payment.id + 1 }} ({{ payment.percentage }}%)</span>
                                <strong class="fs-5">{{ payment.value }}</strong>
                            </li>
                        </ul>
                    </div>

                    <div class="col-12 mt-4">
                        <button @click="clearSimulator" class="btn btn-dark w-100 py-3" type="button">
                            Limpiar Simulador
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
      </div>
    </div>
  </div>
  <!-- Quote End -->


  <!-- Testimonial Start -->
  <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
    <div class="container py-5">
      <div class="section-title text-center position-relative pb-3 mb-4 mx-auto" style="max-width: 600px;">
        <h5 class="fw-bold text-primary text-uppercase">Testimonios</h5>
        <h1 class="mb-0">Lo que nuestros clientes opinan de nosotros</h1>
      </div>
      <div class="owl-carousel testimonial-carousel wow fadeInUp" data-wow-delay="0.6s">
        <div class="testimonial-item bg-light my-4">
          <div class="d-flex align-items-center border-bottom pt-5 pb-4 px-5">
            <img class="img-fluid rounded" src="img/testimonial-1.jpg" style="width: 60px; height: 60px;">
            <div class="ps-4">
              <h4 class="text-primary mb-1">Client Name</h4>
              <small class="text-uppercase">Profession</small>
            </div>
          </div>
          <div class="pt-4 pb-5 px-5">
            Dolor et eos labore, stet justo sed est sed. Diam sed sed dolor stet amet eirmod eos labore diam
          </div>
        </div>
        <div class="testimonial-item bg-light my-4">
          <div class="d-flex align-items-center border-bottom pt-5 pb-4 px-5">
            <img class="img-fluid rounded" src="img/testimonial-2.jpg" style="width: 60px; height: 60px;">
            <div class="ps-4">
              <h4 class="text-primary mb-1">Client Name</h4>
              <small class="text-uppercase">Profession</small>
            </div>
          </div>
          <div class="pt-4 pb-5 px-5">
            Dolor et eos labore, stet justo sed est sed. Diam sed sed dolor stet amet eirmod eos labore diam
          </div>
        </div>
        <div class="testimonial-item bg-light my-4">
          <div class="d-flex align-items-center border-bottom pt-5 pb-4 px-5">
            <img class="img-fluid rounded" src="img/testimonial-3.jpg" style="width: 60px; height: 60px;">
            <div class="ps-4">
              <h4 class="text-primary mb-1">Client Name</h4>
              <small class="text-uppercase">Profession</small>
            </div>
          </div>
          <div class="pt-4 pb-5 px-5">
            Dolor et eos labore, stet justo sed est sed. Diam sed sed dolor stet amet eirmod eos labore diam
          </div>
        </div>
        <div class="testimonial-item bg-light my-4">
          <div class="d-flex align-items-center border-bottom pt-5 pb-4 px-5">
            <img class="img-fluid rounded" src="img/testimonial-4.jpg" style="width: 60px; height: 60px;">
            <div class="ps-4">
              <h4 class="text-primary mb-1">Client Name</h4>
              <small class="text-uppercase">Profession</small>
            </div>
          </div>
          <div class="pt-4 pb-5 px-5">
            Dolor et eos labore, stet justo sed est sed. Diam sed sed dolor stet amet eirmod eos labore diam
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Testimonial End -->

  <!-- Footer Start -->
  <div class="container-fluid bg-dark text-light mt-5 wow fadeInUp" data-wow-delay="0.1s">
    <div class="container">
      <div class="row gx-5">
        <div class="col-lg-4 col-md-6 footer-about">
          <div class="d-flex flex-column align-items-center justify-content-center text-center h-100 bg-primary p-4">
            <a href="index.html" class="navbar-brand">
              <h1 class="m-0 text-white"><i class="fa fa-user-tie me-2"></i>Finance WPA</h1>
            </a>
            <p class="mt-3 mb-4">Lorem diam sit erat dolor elitr et, diam lorem justo amet clita stet eos sit. Elitr
              dolor duo lorem, elitr clita ipsum sea. Diam amet erat lorem stet eos. Diam amet et kasd eos duo.</p>
            <form action="">
              <div class="input-group">
    <Link :href="route('register')" as="button" class="btn btn-dark">
        Registrate
    </Link>
</div>
            </form>
          </div>
        </div>
        <div class="col-lg-8 col-md-6">
          <div class="row gx-5">
            <div class="col-lg-4 col-md-12 pt-5 mb-5">
              <div class="section-title section-title-sm position-relative pb-3 mb-4">
                <h3 class="text-light mb-0">Conocenos</h3>
              </div>
              <div class="d-flex mb-2">
                <i class="bi bi-geo-alt text-primary me-2"></i>
                <p class="mb-0">Calle 123, Bogotá DC, Colombia</p>
              </div>
              <div class="d-flex mb-2">
                <i class="bi bi-envelope-open text-primary me-2"></i>
                <p class="mb-0">soporte@financewpa.com</p>
              </div>
              <div class="d-flex mb-2">
                <i class="bi bi-telephone text-primary me-2"></i>
                <p class="mb-0">+012 345 67890</p>
              </div>
              <div class="d-flex mt-4">
                <a class="btn btn-primary btn-square me-2" href="#"><i class="fab fa-twitter fw-normal"></i></a>
                <a class="btn btn-primary btn-square me-2" href="#"><i class="fab fa-facebook-f fw-normal"></i></a>
                <a class="btn btn-primary btn-square me-2" href="#"><i class="fab fa-linkedin-in fw-normal"></i></a>
                <a class="btn btn-primary btn-square" href="#"><i class="fab fa-instagram fw-normal"></i></a>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="container-fluid text-white" style="background: #061429;">
    <div class="container text-center">
      <div class="row justify-content-end">
        <div class="col-lg-8 col-md-6">
          <div class="d-flex align-items-center justify-content-center" style="height: 75px;">
            <p class="mb-0">&copy; <a class="text-white border-bottom" href="#">Finance WPA</a>. Todos los derechos
              reservados.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Footer End -->


  <!-- Back to Top -->
  <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded back-to-top"><i class="bi bi-arrow-up"></i></a>
</template>

<style scoped>
#facts-section {
  top: -140px;
}
</style>