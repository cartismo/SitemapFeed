<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StoreSettingsTabs from '@/Components/Admin/StoreSettingsTabs.vue';
import {
    MapIcon,
    ArrowLeftIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    XCircleIcon,
    Cog6ToothIcon,
    GlobeAltIcon,
    DocumentTextIcon,
    ClockIcon,
    TrashIcon,
    CubeIcon,
    FolderIcon,
    DocumentIcon,
    NewspaperIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    module: Object,
    stores: Array,
    storeSettings: Object,
    defaultSettings: Object,
    stats: Object,
    changefreqOptions: Array,
    sitemapUrl: String,
});

const storeTabsRef = ref(null);
const saving = ref(false);
const generating = ref(false);
const clearing = ref(false);

const submit = () => {
    if (!storeTabsRef.value) return;

    saving.value = true;
    router.put(route('admin.feeds.sitemap.settings.update'), {
        store_id: storeTabsRef.value.activeStoreId,
        is_enabled: storeTabsRef.value.isEnabled,
        settings: storeTabsRef.value.localSettings,
    }, {
        preserveScroll: true,
        onFinish: () => saving.value = false,
    });
};

const generate = () => {
    generating.value = true;
    router.post(route('admin.feeds.sitemap.generate'), {}, {
        onFinish: () => generating.value = false,
    });
};

const clearSitemaps = () => {
    if (!confirm('Are you sure you want to delete all generated sitemaps?')) return;
    clearing.value = true;
    router.delete(route('admin.feeds.sitemap.clear'), {
        onFinish: () => clearing.value = false,
    });
};

const resetAll = () => {
    if (confirm('Reset all settings to defaults?') && storeTabsRef.value) {
        Object.assign(storeTabsRef.value.localSettings, props.defaultSettings);
    }
};

const hasChanges = computed(() => {
    if (!storeTabsRef.value) return false;
    const currentStoreSettings = props.storeSettings[storeTabsRef.value.activeStoreId];
    if (!currentStoreSettings) return true;
    const original = { ...props.defaultSettings, ...(currentStoreSettings.settings || {}) };
    return JSON.stringify(storeTabsRef.value.localSettings) !== JSON.stringify(original) ||
           storeTabsRef.value.isEnabled !== currentStoreSettings.is_enabled;
});

const lastGenerated = computed(() => {
    if (!storeTabsRef.value) return 'Never';
    const settings = storeTabsRef.value.localSettings;
    if (!settings.last_generated) return 'Never';
    return new Date(settings.last_generated).toLocaleString();
});

const totalUrls = computed(() => {
    if (!storeTabsRef.value) return 1;
    const settings = storeTabsRef.value.localSettings;
    let total = 1; // homepage
    if (settings.include_products) total += props.stats.products;
    if (settings.include_categories) total += props.stats.categories;
    if (settings.include_pages) total += props.stats.pages;
    if (settings.include_blog) total += props.stats.blog_posts;
    return total;
});
</script>

<template>
    <AdminLayout :title="`${module.name} Settings`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('admin.modules.installed.index')"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div class="flex items-center space-x-3">
                        <div class="p-3 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl shadow-lg shadow-orange-500/25">
                            <MapIcon class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">XML Sitemap</h1>
                            <p class="text-sm text-gray-500">SEO Sitemap Generator</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span v-if="hasChanges" class="flex items-center text-sm text-amber-600 font-medium">
                        <span class="w-2 h-2 bg-amber-500 rounded-full mr-2 animate-pulse"></span>
                        Unsaved changes
                    </span>
                    <button
                        type="button"
                        @click="resetAll"
                        class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                    >
                        <ArrowPathIcon class="w-4 h-4 inline mr-2" />
                        Reset
                    </button>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="saving || !hasChanges"
                        class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-orange-500 to-red-600 rounded-xl hover:from-orange-600 hover:to-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-orange-500/25"
                    >
                        <CheckIcon class="w-4 h-4 inline mr-2" />
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </template>

        <StoreSettingsTabs ref="storeTabsRef" :stores="stores" :store-settings="storeSettings" :default-settings="defaultSettings" module-slug="sitemap-feed">
            <template #default="{ store, settings, updateSetting, isEnabled }">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Left Sidebar -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Status Card -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="p-5 border-b border-gray-100">
                                <h3 class="font-semibold text-gray-900">Module Status</h3>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Status</span>
                                    <span
                                        :class="settings.enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                        class="px-3 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ settings.enabled ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Version</span>
                                    <span class="text-sm font-mono text-gray-900">v{{ module.installed_version }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Sitemap</span>
                                    <span
                                        :class="stats.sitemap_exists ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                                        class="px-3 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ stats.sitemap_exists ? 'Generated' : 'Not Generated' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Card -->
                        <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-lg p-5 text-white">
                            <div class="flex items-center space-x-3 mb-4">
                                <MapIcon class="w-8 h-8 opacity-80" />
                                <div>
                                    <p class="text-sm opacity-80">Total URLs</p>
                                    <p class="text-2xl font-bold">{{ totalUrls.toLocaleString() }}</p>
                                </div>
                            </div>
                            <div class="space-y-2 pt-3 border-t border-white/20">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center opacity-80">
                                        <CubeIcon class="w-4 h-4 mr-2" /> Products
                                    </span>
                                    <span class="font-medium">{{ stats.products.toLocaleString() }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center opacity-80">
                                        <FolderIcon class="w-4 h-4 mr-2" /> Categories
                                    </span>
                                    <span class="font-medium">{{ stats.categories.toLocaleString() }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center opacity-80">
                                        <DocumentIcon class="w-4 h-4 mr-2" /> Pages
                                    </span>
                                    <span class="font-medium">{{ stats.pages.toLocaleString() }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center opacity-80">
                                        <NewspaperIcon class="w-4 h-4 mr-2" /> Blog Posts
                                    </span>
                                    <span class="font-medium">{{ stats.blog_posts.toLocaleString() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Sitemap URL -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="p-5 border-b border-gray-100">
                                <h3 class="font-semibold text-gray-900">Sitemap URL</h3>
                            </div>
                            <div class="p-5">
                                <a
                                    :href="sitemapUrl"
                                    target="_blank"
                                    class="flex items-center space-x-2 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                                >
                                    <GlobeAltIcon class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                    <code class="text-sm text-orange-600 break-all">{{ sitemapUrl }}</code>
                                </a>
                            </div>
                        </div>

                        <!-- Generate Section -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="p-5 border-b border-gray-100">
                                <h3 class="font-semibold text-gray-900">Generate Sitemap</h3>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <ClockIcon class="w-4 h-4 mr-2" />
                                    Last: {{ lastGenerated }}
                                </div>
                                <button
                                    type="button"
                                    @click="generate"
                                    :disabled="!settings.enabled || generating"
                                    class="w-full px-4 py-3 text-sm font-medium text-white bg-gradient-to-r from-orange-500 to-red-600 rounded-xl hover:from-orange-600 hover:to-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center"
                                >
                                    <ArrowPathIcon :class="generating ? 'animate-spin' : ''" class="w-4 h-4 mr-2" />
                                    {{ generating ? 'Generating...' : 'Generate Now' }}
                                </button>
                                <button
                                    v-if="stats.sitemap_exists"
                                    type="button"
                                    @click="clearSitemaps"
                                    :disabled="clearing"
                                    class="w-full px-4 py-3 text-sm font-medium text-red-600 bg-red-50 rounded-xl hover:bg-red-100 disabled:opacity-50 transition-all flex items-center justify-center"
                                >
                                    <TrashIcon class="w-4 h-4 mr-2" />
                                    {{ clearing ? 'Clearing...' : 'Clear Sitemaps' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - Settings -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Enable/Disable -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div :class="settings.enabled ? 'bg-green-100' : 'bg-gray-100'" class="p-3 rounded-xl transition-colors">
                                        <component :is="settings.enabled ? CheckCircleIcon : XCircleIcon"
                                            :class="settings.enabled ? 'text-green-600' : 'text-gray-400'"
                                            class="w-6 h-6"
                                        />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">Enable XML Sitemap</h3>
                                        <p class="text-sm text-gray-500">Generate and serve XML sitemaps for search engines for {{ store?.name }}</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="updateSetting('enabled', !settings.enabled)"
                                    :class="settings.enabled ? 'bg-green-500' : 'bg-gray-300'"
                                    class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                >
                                    <span
                                        :class="settings.enabled ? 'translate-x-5' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                    />
                                </button>
                            </div>
                        </div>

                        <!-- Content Settings -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <DocumentTextIcon class="w-5 h-5 text-gray-400" />
                                    <h2 class="font-semibold text-gray-900">Content to Include</h2>
                                </div>
                            </div>
                            <div class="p-6 grid grid-cols-2 gap-4">
                                <label
                                    v-for="(data, key) in {
                                        include_products: {label: 'Products', count: stats.products, icon: CubeIcon},
                                        include_categories: {label: 'Categories', count: stats.categories, icon: FolderIcon},
                                        include_pages: {label: 'Pages', count: stats.pages, icon: DocumentIcon},
                                        include_blog: {label: 'Blog Posts', count: stats.blog_posts, icon: NewspaperIcon}
                                    }"
                                    :key="key"
                                    class="relative flex items-center p-4 rounded-xl cursor-pointer transition-all"
                                    :class="settings[key]
                                        ? 'bg-orange-50 border-2 border-orange-200'
                                        : 'bg-gray-50 border-2 border-transparent hover:bg-gray-100'"
                                >
                                    <input type="checkbox" :checked="settings[key]" @change="updateSetting(key, $event.target.checked)" class="sr-only" />
                                    <div
                                        class="w-10 h-6 rounded-full relative transition-colors mr-3"
                                        :class="settings[key] ? 'bg-orange-500' : 'bg-gray-300'"
                                    >
                                        <div
                                            class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-transform"
                                            :class="settings[key] ? 'translate-x-5' : 'translate-x-1'"
                                        ></div>
                                    </div>
                                    <component :is="data.icon" class="w-5 h-5 text-gray-400 mr-2" />
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-gray-700">{{ data.label }}</span>
                                        <span class="text-xs text-gray-500 ml-2">({{ data.count }})</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Frequency & Priority Settings -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <Cog6ToothIcon class="w-5 h-5 text-gray-400" />
                                    <h2 class="font-semibold text-gray-900">Change Frequency & Priority</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                                    <!-- Homepage -->
                                    <div class="space-y-4">
                                        <h4 class="font-medium text-gray-700">Homepage</h4>
                                        <div>
                                            <label class="block text-sm text-gray-500 mb-1">Priority (0-1)</label>
                                            <input type="number" :value="settings.priority_homepage" @input="updateSetting('priority_homepage', parseFloat($event.target.value))" min="0" max="1" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                                        </div>
                                    </div>

                                    <!-- Products -->
                                    <div class="space-y-4">
                                        <h4 class="font-medium text-gray-700">Products</h4>
                                        <div>
                                            <label class="block text-sm text-gray-500 mb-1">Frequency</label>
                                            <select :value="settings.changefreq_products" @change="updateSetting('changefreq_products', $event.target.value)" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                                <option v-for="opt in changefreqOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-500 mb-1">Priority (0-1)</label>
                                            <input type="number" :value="settings.priority_products" @input="updateSetting('priority_products', parseFloat($event.target.value))" min="0" max="1" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                                        </div>
                                    </div>

                                    <!-- Categories -->
                                    <div class="space-y-4">
                                        <h4 class="font-medium text-gray-700">Categories</h4>
                                        <div>
                                            <label class="block text-sm text-gray-500 mb-1">Frequency</label>
                                            <select :value="settings.changefreq_categories" @change="updateSetting('changefreq_categories', $event.target.value)" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                                <option v-for="opt in changefreqOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-500 mb-1">Priority (0-1)</label>
                                            <input type="number" :value="settings.priority_categories" @input="updateSetting('priority_categories', parseFloat($event.target.value))" min="0" max="1" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                                        </div>
                                    </div>

                                    <!-- Pages -->
                                    <div class="space-y-4">
                                        <h4 class="font-medium text-gray-700">Pages</h4>
                                        <div>
                                            <label class="block text-sm text-gray-500 mb-1">Frequency</label>
                                            <select :value="settings.changefreq_pages" @change="updateSetting('changefreq_pages', $event.target.value)" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                                <option v-for="opt in changefreqOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-500 mb-1">Priority (0-1)</label>
                                            <input type="number" :value="settings.priority_pages" @input="updateSetting('priority_pages', parseFloat($event.target.value))" min="0" max="1" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Blog settings (shown when blog is enabled) -->
                                <div v-if="settings.include_blog" class="mt-6 pt-6 border-t border-gray-200">
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                                        <div class="space-y-4">
                                            <h4 class="font-medium text-gray-700">Blog Posts</h4>
                                            <div>
                                                <label class="block text-sm text-gray-500 mb-1">Frequency</label>
                                                <select :value="settings.changefreq_blog" @change="updateSetting('changefreq_blog', $event.target.value)" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                                    <option v-for="opt in changefreqOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm text-gray-500 mb-1">Priority (0-1)</label>
                                                <input type="number" :value="settings.priority_blog" @input="updateSetting('priority_blog', parseFloat($event.target.value))" min="0" max="1" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Settings -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <Cog6ToothIcon class="w-5 h-5 text-gray-400" />
                                    <h2 class="font-semibold text-gray-900">Advanced Settings</h2>
                                </div>
                            </div>
                            <div class="p-6 space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Max URLs per Sitemap</label>
                                    <input
                                        type="number"
                                        :value="settings.max_urls_per_sitemap"
                                        @input="updateSetting('max_urls_per_sitemap', parseInt($event.target.value))"
                                        min="1000"
                                        max="50000"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500"
                                    />
                                    <p class="text-xs text-gray-500 mt-1">Maximum 50,000 URLs per sitemap file (Google limit)</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <label
                                        class="relative flex items-center p-4 rounded-xl cursor-pointer transition-all"
                                        :class="settings.ping_google
                                            ? 'bg-orange-50 border-2 border-orange-200'
                                            : 'bg-gray-50 border-2 border-transparent hover:bg-gray-100'"
                                    >
                                        <input type="checkbox" :checked="settings.ping_google" @change="updateSetting('ping_google', $event.target.checked)" class="sr-only" />
                                        <div
                                            class="w-10 h-6 rounded-full relative transition-colors mr-3"
                                            :class="settings.ping_google ? 'bg-orange-500' : 'bg-gray-300'"
                                        >
                                            <div
                                                class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-transform"
                                                :class="settings.ping_google ? 'translate-x-5' : 'translate-x-1'"
                                            ></div>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-700">Ping Google</span>
                                            <p class="text-xs text-gray-500">Notify on update</p>
                                        </div>
                                    </label>

                                    <label
                                        class="relative flex items-center p-4 rounded-xl cursor-pointer transition-all"
                                        :class="settings.ping_bing
                                            ? 'bg-orange-50 border-2 border-orange-200'
                                            : 'bg-gray-50 border-2 border-transparent hover:bg-gray-100'"
                                    >
                                        <input type="checkbox" :checked="settings.ping_bing" @change="updateSetting('ping_bing', $event.target.checked)" class="sr-only" />
                                        <div
                                            class="w-10 h-6 rounded-full relative transition-colors mr-3"
                                            :class="settings.ping_bing ? 'bg-orange-500' : 'bg-gray-300'"
                                        >
                                            <div
                                                class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-transform"
                                                :class="settings.ping_bing ? 'translate-x-5' : 'translate-x-1'"
                                            ></div>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-700">Ping Bing</span>
                                            <p class="text-xs text-gray-500">Notify on update</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </StoreSettingsTabs>
    </AdminLayout>
</template>