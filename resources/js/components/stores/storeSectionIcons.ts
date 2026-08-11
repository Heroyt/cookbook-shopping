import {
    AppleIcon,
    BeefIcon,
    CarrotIcon,
    CookieIcon,
    CroissantIcon,
    CrossIcon,
    FishIcon,
    MilkIcon,
    PackageIcon,
    SnowflakeIcon,
    SparklesIcon,
    WineIcon,
} from '@lucide/vue';
import type { Component } from 'vue';
import type { StoreSectionIconName } from '@/types';

export const storeSectionIconOptions = [
    { value: 'apple', label: 'Ovoce', component: AppleIcon },
    { value: 'carrot', label: 'Zelenina', component: CarrotIcon },
    { value: 'croissant', label: 'Pečivo', component: CroissantIcon },
    { value: 'milk', label: 'Mléčné', component: MilkIcon },
    { value: 'beef', label: 'Maso', component: BeefIcon },
    { value: 'fish', label: 'Ryby', component: FishIcon },
    { value: 'snowflake', label: 'Mražené', component: SnowflakeIcon },
    { value: 'wine', label: 'Nápoje', component: WineIcon },
    { value: 'cookie', label: 'Sladkosti', component: CookieIcon },
    { value: 'package', label: 'Ostatní', component: PackageIcon },
    { value: 'sparkles', label: 'Drogerie', component: SparklesIcon },
    { value: 'cross', label: 'Lékárna', component: CrossIcon },
] as const satisfies ReadonlyArray<{
    value: StoreSectionIconName;
    label: string;
    component: Component;
}>;

export const storeSectionIconComponents = Object.fromEntries(
    storeSectionIconOptions.map((option) => [option.value, option.component]),
) as Record<StoreSectionIconName, Component>;
