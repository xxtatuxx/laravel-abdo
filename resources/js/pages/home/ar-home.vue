<script setup lang="ts">
import AppLayout from '@/layouts/AR-HomeLayout.vue';
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { usePage, router as inertia } from '@inertiajs/vue3';
import { Episode } from '@/types';

// ----- السلايدر العلوي -----
const slides = ref([
  { id: 1, image: '/animes/onepiece_cover.jpg', title: 'Slide 1', subtitle: 'Subtitle 1' },
  { id: 2, image: '/animes/naruto.jpg', title: 'Slide 2', subtitle: 'Subtitle 2' },
  { id: 3, image: '/animes/onepiece_cover.jpg', title: 'Slide 3', subtitle: 'Subtitle 3' },
]);

const currentSlide = ref(0);
const sliderTrack = ref<HTMLDivElement>();
let interval: number;
let startX = 0;
let endX = 0;
let isDragging = false;
let dragStartX = 0;
let dragCurrentX = 0;
let dragOffset = 0;
const transitionEnabled = ref(true);

// ----- دوال السلايدر العلوي -----
function nextSlide() {
  transitionEnabled.value = true;
  currentSlide.value = (currentSlide.value + 1) % slides.value.length;
}

function prevSlide() {
  transitionEnabled.value = true;
  currentSlide.value = (currentSlide.value - 1 + slides.value.length) % slides.value.length;
}

function goToSlide(index: number) {
  transitionEnabled.value = true;
  currentSlide.value = index;
}

// التبديل التلقائي
onMounted(() => {
  interval = setInterval(nextSlide, 4000);
});

onUnmounted(() => {
  clearInterval(interval);
});

// ---- السحب بالأصبع (Touch Swipe) ----
function handleTouchStart(e: TouchEvent) {
  transitionEnabled.value = false;
  startX = e.touches[0].clientX;
}

function handleTouchMove(e: TouchEvent) {
  if (!sliderTrack.value) return;
  
  const currentX = e.touches[0].clientX;
  const diff = startX - currentX;
  const slideWidth = sliderTrack.value.offsetWidth;
  
  dragOffset = (-currentSlide.value * slideWidth) - (diff / slideWidth * 100);
  sliderTrack.value.style.transform = `translateX(${dragOffset}%)`;
}

function handleTouchEnd(e: TouchEvent) {
  transitionEnabled.value = true;
  endX = e.changedTouches[0].clientX;
  const diff = startX - endX;
  
  if (Math.abs(diff) > 50) {
    if (diff > 0) nextSlide();
    else prevSlide();
  } else {
    // إذا كانت المسافة غير كافية، نعود للسلايد الحالي
    nextTick(() => {
      if (sliderTrack.value) {
        sliderTrack.value.style.transform = `translateX(-${currentSlide.value * 100}%)`;
      }
    });
  }
}

// ---- السحب بالماوس للسلايدر العلوي ----
function handleMouseDown(e: MouseEvent) {
  e.preventDefault();
  isDragging = true;
  transitionEnabled.value = false;
  dragStartX = e.clientX;
  
  document.addEventListener('mousemove', handleMouseMove);
  document.addEventListener('mouseup', handleMouseUp);
  
  // إيقاف التبديل التلقائي مؤقتاً
  clearInterval(interval);
}

function handleMouseMove(e: MouseEvent) {
  if (!isDragging || !sliderTrack.value) return;
  
  dragCurrentX = e.clientX;
  const diff = dragStartX - dragCurrentX;
  const slideWidth = sliderTrack.value.offsetWidth;
  
  // حساب الإزاحة بنسبة مئوية
  dragOffset = (-currentSlide.value * 100) - (diff / slideWidth * 100);
  sliderTrack.value.style.transform = `translateX(${dragOffset}%)`;
}

function handleMouseUp() {
  if (!isDragging) return;
  
  const diff = dragStartX - dragCurrentX;
  const slideWidth = sliderTrack.value?.offsetWidth || 0;
  const threshold = slideWidth * 0.1; // 10% من عرض السلايد
  
  transitionEnabled.value = true;
  
  if (Math.abs(diff) > threshold) {
    if (diff > 0) nextSlide();
    else prevSlide();
  } else {
    // إذا كانت المسافة غير كافية، نعود للسلايد الحالي
    nextTick(() => {
      if (sliderTrack.value) {
        sliderTrack.value.style.transform = `translateX(-${currentSlide.value * 100}%)`;
      }
    });
  }
  
  isDragging = false;
  document.removeEventListener('mousemove', handleMouseMove);
  document.removeEventListener('mouseup', handleMouseUp);
  
  // إعادة تشغيل التبديل التلقائي
  interval = setInterval(nextSlide, 4000);
}

// ----- حلقات Carousel السفلي -----
const page = usePage<{
  episodes: { data: Episode[]; next_page_url?: string; current_page?: number };
  filters?: { search?: string };
}>();

const episodes = ref<Episode[]>(page.props.episodes.data || []);
const nextPageUrl = ref(page.props.episodes.next_page_url || null);
const currentPage = ref(page.props.episodes.current_page || 1);
const loadingMore = ref(false);
const search = ref(page.props.filters?.search || '');
const carouselRef = ref<HTMLDivElement | null>(null);

// متغيرات السحب للكاروسيل السفلي
let isCarouselDragging = false;
let carouselStartX = 0;
let carouselScrollLeft = 0;

// ---- السحب بالماوس للكاروسيل السفلي ----
function handleCarouselMouseDown(e: MouseEvent) {
  if (!carouselRef.value) return;
  
  e.preventDefault();
  isCarouselDragging = true;
  carouselStartX = e.pageX;
  carouselScrollLeft = carouselRef.value.scrollLeft;
  
  carouselRef.value.style.cursor = 'grabbing';
  carouselRef.value.style.scrollBehavior = 'auto';
  carouselRef.value.style.userSelect = 'none';
  
  document.addEventListener('mousemove', handleCarouselMouseMove);
  document.addEventListener('mouseup', handleCarouselMouseUp);
}

function handleCarouselMouseMove(e: MouseEvent) {
  if (!isCarouselDragging || !carouselRef.value) return;
  
  e.preventDefault();
  const x = e.pageX;
  const walk = (x - carouselStartX) * 1.5; // سرعة السحب
  carouselRef.value.scrollLeft = carouselScrollLeft - walk;
}

function handleCarouselMouseUp() {
  if (!carouselRef.value) return;
  
  isCarouselDragging = false;
  carouselRef.value.style.cursor = 'grab';
  carouselRef.value.style.scrollBehavior = 'smooth';
  carouselRef.value.style.userSelect = 'auto';
  
  document.removeEventListener('mousemove', handleCarouselMouseMove);
  document.removeEventListener('mouseup', handleCarouselMouseUp);
}

// البحث
watch(search, (value) => {
  currentPage.value = 1;
  inertia.get('/ar/home', { search: value, page: currentPage.value }, {
    preserveState: true,
    preserveScroll: true,
    only: ['episodes', 'filters'],
    onSuccess: (pageResponse) => {
      episodes.value = pageResponse.props.episodes.data;
      nextPageUrl.value = pageResponse.props.episodes.next_page_url || null;
    },
  });
});

// تحميل المزيد عند التمرير
const loadMoreEpisodes = () => {
  if (!nextPageUrl.value || loadingMore.value) return;
  loadingMore.value = true;
  currentPage.value++;

  inertia.get('/ar/home', { page: currentPage.value, search: search.value }, {
    preserveState: true,
    preserveScroll: true,
    only: ['episodes'],
    onSuccess: (pageResponse) => {
      const newData = pageResponse.props.episodes.data;
      episodes.value.push(...newData);
      nextPageUrl.value = pageResponse.props.episodes.next_page_url || null;
    },
    onFinish: () => { loadingMore.value = false; },
  });
};

// مراقبة تمرير الشريط
const onCarouselScroll = () => {
  const el = carouselRef.value;
  if (!el) return;
  if (el.scrollLeft + el.clientWidth >= el.scrollWidth - 50) {
    loadMoreEpisodes();
  }
};

// فتح الحلقة عند الضغط
const goToEpisode = (id: number) => {
  inertia.visit(`/episodes/${id}`);
};

// أزرار تحريك الشريط
const scrollLeft = () => {
  carouselRef.value?.scrollBy({ left: -300, behavior: 'smooth' });
};

const scrollRight = () => {
  carouselRef.value?.scrollBy({ left: 300, behavior: 'smooth' });
};
</script>

<template>
  <Head title="home" />
  <AppLayout>
    <!-- السلايدر العلوي مع خاصية السحب بالماوس -->
    <div
      class="slider-main-container"
      @touchstart="handleTouchStart"
      @touchmove="handleTouchMove"
      @touchend="handleTouchEnd"
      @mousedown="handleMouseDown"
    >
      <!-- الصور -->
      <div
        ref="sliderTrack"
        class="slider-track"
        :class="{ 'transition-enabled': transitionEnabled }"
        :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
      >
        <div
          v-for="slide in slides"
          :key="slide.id"
          class="slide-item"
        >
          <img
            :src="slide.image"
            :alt="slide.title"
            class="slide-image"
          />
          <!-- النص -->
          <div class="slide-content">
            <h2 class="slide-title">{{ slide.title }}</h2>
            <p class="slide-subtitle">{{ slide.subtitle }}</p>
          </div>
        </div>
      </div>

      <!-- أزرار تنقل -->
      <button
        @click="prevSlide"
        @mousedown.prevent
        class="slider-nav-btn slider-prev"
      >‹</button>
      <button
        @click="nextSlide"
        @mousedown.prevent
        class="slider-nav-btn slider-next"
      >›</button>

      <!-- الدوائر -->
      <div class="slider-indicators">
        <div class="indicators-container">
          <button
            v-for="(s, i) in slides"
            :key="i"
            @click="goToSlide(i)"
            @mousedown.prevent
            class="indicator-btn"
            :class="{ active: i === currentSlide }"
          ></button>
        </div>
      </div>
    </div>

    <!-- عنوان الحلقات + أزرار الشريط -->
    <div class="episodes-header">
      <h2 class="episodes-title">آخر الحلقات المضافة</h2>
      <div class="carousel-controls">
        <button
          @click="scrollLeft"
          @mousedown.prevent
          class="carousel-nav-btn"
        >‹</button>
        <button
          @click="scrollRight"
          @mousedown.prevent
          class="carousel-nav-btn"
        >›</button>
      </div>
    </div>

    <!-- Carousel الحلقات مع خاصية السحب بالماوس -->
    <div
      ref="carouselRef"
      @scroll="onCarouselScroll"
      @mousedown="handleCarouselMouseDown"
      class="episodes-carousel"
    >
      <div
        v-for="episode in episodes"
        :key="episode.id"
        @click="goToEpisode(episode.id)"
        class="episode-card"
      >
        <div class="episode-thumbnail">
          <img
            v-if="episode.thumbnail"
            :src="`/storage/${episode.thumbnail}`"
            alt="thumbnail"
            class="thumbnail-image"
          />
          <div
            v-else
            class="thumbnail-placeholder"
          >
            No Image
          </div>
          <div
            v-if="episode.is_published"
            class="episode-badge live-badge"
          >
            يعرض الآن
          </div>
          <div
            v-if="episode.video_format"
            class="episode-badge format-badge"
          >
            {{ episode.video_format }}
          </div>
        </div>
        <div class="episode-info">
          <span class="episode-title">{{ episode.title }}</span>
          <span class="episode-number">الحلقة {{ episode.episode_number }}</span>
        </div>
      </div>

      <div v-if="loadingMore" class="loading-skeletons">
        <div
          v-for="n in 3"
          :key="n"
          class="episode-skeleton"
        ></div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* الأساسيات */
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

html, body {
  margin: 0;
  padding: 0;
  overflow-x: hidden;
}

/* السلايدر العلوي */
.slider-main-container {
  position: relative;
  width: 100vw;
  left: 50%;
  transform: translateX(-50%);
  overflow: hidden;
  cursor: grab;
  user-select: none;
  -webkit-user-select: none;
  -webkit-tap-highlight-color: transparent;
}

.slider-main-container:active {
  cursor: grabbing;
}

.slider-track {
  display: flex;
  height: 350px;
  transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.slider-track.transition-enabled {
  transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.slide-item {
  flex: 0 0 100%;
  height: 100%;
  position: relative;
}

.slide-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  pointer-events: none;
  -webkit-user-drag: none;
}

.slide-content {
  position: absolute;
  padding: 1rem;
  color: white;
  border-radius: 0.5rem;
  bottom: 2rem;
  right: 2rem;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(12px);
}

.slide-title {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.slide-subtitle {
  font-size: 1rem;
  opacity: 0.9;
}

/* أزرار التنقل */
.slider-nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  padding: 0.75rem;
  color: white;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.5);
  border: none;
  cursor: pointer;
  transition: background-color 0.2s;
  -webkit-tap-highlight-color: transparent;
  user-select: none;
}

.slider-nav-btn:hover {
  background: rgba(0, 0, 0, 0.7);
}

.slider-nav-btn:active {
  background: rgba(0, 0, 0, 0.8);
}

.slider-prev {
  left: 1rem;
}

.slider-next {
  right: 1rem;
}

/* الدوائر */
.slider-indicators {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 4rem;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding-bottom: 0.75rem;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);
}

.indicators-container {
  display: flex;
  gap: 0.75rem;
}

.indicator-btn {
  width: 0.75rem;
  height: 0.75rem;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  transition: all 0.3s;
  background: rgba(255, 255, 255, 0.4);
  -webkit-tap-highlight-color: transparent;
}

.indicator-btn:hover {
  background: rgba(255, 255, 255, 0.7);
}

.indicator-btn.active {
  background: white;
  transform: scale(1.1);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* عنوان الحلقات */
.episodes-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1rem;
  margin-top: 1.5rem;
  margin-bottom: 1rem;
}

.episodes-title {
  font-size: 1.5rem;
  font-weight: bold;
  font-family: 'Cairo', sans-serif;
}

.carousel-controls {
  display: flex;
  gap: 0.5rem;
}

.carousel-nav-btn {
  padding: 0.5rem 0.75rem;
  color: white;
  background: rgb(147, 51, 234);
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: background-color 0.2s;
  -webkit-tap-highlight-color: transparent;
}

.carousel-nav-btn:hover {
  background: rgb(126, 34, 206);
}

.carousel-nav-btn:active {
  background: rgb(107, 33, 168);
}

/* كاروسيل الحلقات */
.episodes-carousel {
  display: flex;
  gap: 1rem;
  padding: 0 1rem 1rem;
  overflow-x: auto;
  scroll-behavior: smooth;
  cursor: grab;
  user-select: none;
  -webkit-user-select: none;
  -webkit-overflow-scrolling: touch;
}

.episodes-carousel:active {
  cursor: grabbing;
}

.episode-card {
  flex: 0 0 auto;
  width: 12rem;
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: transform 0.2s;
  overflow: hidden;
  -webkit-tap-highlight-color: transparent;
}

.episode-card:hover {
  transform: scale(1.05);
}

.episode-thumbnail {
  position: relative;
  width: 100%;
  height: 10rem;
}

.thumbnail-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 0.5rem 0.5rem 0 0;
}

.thumbnail-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  color: #6b7280;
  background: #e5e7eb;
  border-radius: 0.5rem 0.5rem 0 0;
}

.episode-badge {
  position: absolute;
  font-size: 0.625rem;
  font-weight: bold;
  border-radius: 9999px;
  padding: 0.25rem 0.5rem;
  color: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.live-badge {
  top: 0.5rem;
  left: 0.5rem;
  background: rgb(22, 163, 74);
}

.format-badge {
  top: 0.5rem;
  right: 0.5rem;
  background: rgb(37, 99, 235);
}

.episode-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.5rem;
}

.episode-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.episode-number {
  font-size: 0.75rem;
  color: #6b7280;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* التحميل */
.loading-skeletons {
  display: flex;
  gap: 1rem;
}

.episode-skeleton {
  flex: 0 0 auto;
  width: 12rem;
  height: 14rem;
  padding: 1rem;
  background: #d1d5db;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

/* تحسينات للجوال */
@media (max-width: 768px) {
  .slider-track {
    height: 280px;
  }
  
  .slide-content {
    bottom: 1rem;
    right: 1rem;
    padding: 0.75rem;
  }
  
  .slide-title {
    font-size: 1.25rem;
  }
  
  .slide-subtitle {
    font-size: 0.875rem;
  }
  
  .slider-nav-btn {
    padding: 0.5rem;
  }
  
  .episode-card {
    width: 10rem;
  }
  
  .episode-thumbnail {
    height: 8rem;
  }
}
</style>
