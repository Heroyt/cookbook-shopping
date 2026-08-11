import {
    AppleIcon,
    BananaIcon,
    BeefIcon,
    BroomIcon,
    CakeSliceIcon,
    CarrotIcon,
    CookieIcon,
    CookingPotIcon,
    CroissantIcon,
    CrossIcon,
    CupSodaIcon,
    DrumstickIcon,
    EggIcon,
    FishIcon,
    HamIcon,
    MilkIcon,
    NutIcon,
    PackageIcon,
    PizzaIcon,
    SaladIcon,
    SnowflakeIcon,
    SoupIcon,
    SparklesIcon,
    WheatIcon,
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
    { value: 'banana', label: 'Banány', component: BananaIcon },
    { value: 'egg', label: 'Vejce', component: EggIcon },
    { value: 'nut', label: 'Ořechy', component: NutIcon },
    {
        value: 'wheat',
        label: 'Obiloviny a těstoviny',
        component: WheatIcon,
    },
    {
        value: 'soup',
        label: 'Konzervy a hotová jídla',
        component: SoupIcon,
    },
    { value: 'cake-slice', label: 'Pečení', component: CakeSliceIcon },
    { value: 'cup-soda', label: 'Jogurty', component: CupSodaIcon },
    { value: 'ham', label: 'Uzeniny', component: HamIcon },
    { value: 'drumstick', label: 'Drůbež', component: DrumstickIcon },
    { value: 'salad', label: 'Saláty', component: SaladIcon },
    { value: 'broom', label: 'Úklid', component: BroomIcon },
    {
        value: 'cooking-pot',
        label: 'Koření a vaření',
        component: CookingPotIcon,
    },
    { value: 'cheese', label: 'Sýry', component: PizzaIcon },
] as const satisfies ReadonlyArray<{
    value: StoreSectionIconName;
    label: string;
    component: Component;
}>;

export const storeSectionIconComponents = Object.fromEntries(
    storeSectionIconOptions.map((option) => [option.value, option.component]),
) as Record<StoreSectionIconName, Component>;
