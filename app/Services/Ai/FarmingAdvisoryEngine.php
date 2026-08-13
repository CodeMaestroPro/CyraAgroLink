<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Answers open-ended farming questions via specialty handlers, knowledge match,
 * and optional remote LLM (when configured).
 */
class FarmingAdvisoryEngine
{
    public function __construct(
        protected FarmingKnowledgeBase $knowledge,
        protected LlmFarmingAdvisor $llm,
    ) {
    }

    /**
     * @return array{type: string, body?: string, payload?: array<string, mixed>}
     */
    public function answer(User $user, string $prompt): array
    {
        $text = Str::lower(trim($prompt));
        $name = $this->firstName($user->name);

        if ($text === '') {
            return [
                'type' => 'text',
                'body' => "Hello {$name}. Ask me any farming question—crops, livestock, fish, soil, pests, markets, or farm business.",
            ];
        }

        if ($diagnosis = $this->maizeBlightDiagnosis($text)) {
            return $diagnosis;
        }

        if ($specialty = $this->specialtyTextReply($text)) {
            return ['type' => 'text', 'body' => $specialty];
        }

        if ($this->looksNonFarming($text)) {
            return [
                'type' => 'text',
                'body' => "I focus on farming and agribusiness, {$name}. Ask about crops, poultry, livestock, fish ponds, soil, fertilizer, pests, irrigation, storage, or farm business—and I will answer directly.",
            ];
        }

        if ($this->llm->enabled()) {
            $remote = $this->llm->answer($prompt, $name);
            if ($remote !== null) {
                return ['type' => 'text', 'body' => $remote];
            }
        }

        return ['type' => 'text', 'body' => $this->composeFromKnowledge($text, $name)];
    }

    protected function firstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = $parts[0] ?? '';

        return $first !== '' ? $first : 'Farmer';
    }

    /**
     * @return array{type: string, payload: array<string, mixed>}|null
     */
    protected function maizeBlightDiagnosis(string $text): ?array
    {
        if (! $this->mentions($text, ['blight', 'leaf spot', 'maize leaf', 'corn leaf', 'yellow leaves', 'disease on my maize'])) {
            return null;
        }

        return [
            'type' => 'diagnosis',
            'payload' => [
                'intro' => "I've reviewed your description. Your maize may be showing",
                'diagnosis' => 'Northern Corn Leaf Blight.',
                'recommendations_title' => 'Recommendations:',
                'recommendations' => [
                    'Use resistant varieties',
                    'Apply Fungicide (Azoxystrobin)',
                    'Ensure proper spacing',
                    'Reduce leaf wetness',
                ],
                'image' => 'images/ai/maize-blight.jpg',
                'cta_prompt' => 'Would you like a detailed treatment guide?',
                'treatment_guide' => 'Apply a strobilurin fungicide such as Azoxystrobin at early lesion stage, improve airflow with wider spacing, remove heavily infected lower leaves, and avoid prolonged leaf wetness during evening irrigation.',
            ],
        ];
    }

    protected function specialtyTextReply(string $text): ?string
    {
        if ($this->isMaturityQuestion($text)) {
            return $this->maturityReply($text);
        }

        if ($this->isPlantingTimeQuestion($text)) {
            return $this->plantingTimeReply($text);
        }

        if ($this->isSoilTypeQuestion($text)) {
            return $this->soilTypeReply($text);
        }

        if ($this->isIrrigationFrequencyQuestion($text)) {
            return $this->irrigationFrequencyReply($text);
        }

        if ($this->isRainfallQuestion($text)) {
            return $this->rainfallReply($text);
        }

        if ($this->isPestsDiseasesListQuestion($text)) {
            return $this->pestsDiseasesListReply($text);
        }

        if ($this->isDiseaseControlQuestion($text)) {
            return $this->diseaseControlReply($text);
        }

        if ($this->isFeedQuestion($text)) {
            return $this->feedReply($text);
        }

        if ($this->isStorageQuestion($text)) {
            return $this->storageReply($text);
        }

        if ($this->isFertilizerQuestion($text)) {
            return $this->fertilizerReply($text);
        }

        if ($this->isSpacingQuestion($text)) {
            return $this->spacingReply($text);
        }

        if ($this->isHowToPlantQuestion($text)) {
            return $this->howToPlantReply($text);
        }

        if ($this->isStartEnterpriseQuestion($text)) {
            return $this->startEnterpriseReply($text);
        }

        return null;
    }

    protected function plantingTimeReply(string $text): string
    {
        if ($this->mentions($text, ['maize', 'corn'])) {
            return 'Best time to plant maize in Nigeria is when the rains are established—usually after 2–3 reliable showers so the topsoil is moist to seed depth (about 5 cm), not on the first light drizzle. '
                .'By zone (rainfed): South / derived savanna often March–May for the main season (a second crop may fit August–September where the bimodal rains allow); '
                .'Northern Guinea savanna typically May–June; Sudan savanna / far north often June–July once rains stabilize. '
                .'Irrigated maize can be planted outside these windows whenever temperature and water are adequate. '
                .'Choose extra-early or early hybrids if your season is short, and plant early in the local window so grain fill is not cut short by late-season drought. '
                .'Tell me your state or agro-ecology for a tighter calendar.';
        }

        if ($this->mentions($text, ['cassava'])) {
            return 'Cassava is best planted at the start of reliable rains so cuttings establish before dry stress—often March–May in the south and May–July farther north. '
                .'Avoid waterlogged planting spots. Irrigated or inland-valley sites can extend the window. Share your state for a tighter month range.';
        }

        if ($this->mentions($text, ['rice'])) {
            return 'Rice planting follows water availability: rainfed upland after rains stabilize; lowland/paddy when bunds hold water and nurseries are ready—often aligned with the main wet season in your zone (commonly April–July depending on north vs south). '
                .'Transplant or direct-seed according to your system; do not flood a dry nursery. Name upland vs lowland and your state for finer timing.';
        }

        if ($this->mentions($text, ['yam'])) {
            return 'Yam is usually planted at the onset of rains (often February–April in many southern/middle-belt areas, later where rains start later) using healthy setts. '
                .'Early planting with mulch/staking plans helps vines catch the full season. Confirm your location for a tighter window.';
        }

        if ($this->mentions($text, ['tomato', 'pepper', 'vegetable'])) {
            return 'Vegetables: time transplanting for mild temperatures and reliable water—often early rains or under irrigation in the dry season for market tomatoes/peppers. '
                .'Avoid peak flood months on open beds. Irrigation lets you plant for price peaks rather than only the calendar.';
        }

        return 'Plant after reliable moisture (or when irrigation is ready), matching variety to your agro-ecology. '
            .'Southern Nigeria often starts earlier than the far north. Stagger plantings when labour and cashflow allow. '
            .'Name the crop (e.g. maize, cassava, rice, yam) and your state for a precise planting window.';
    }

    protected function maturityReply(string $text): string
    {
        if ($this->mentions($text, ['maize', 'corn'])) {
            return 'Maize maturity depends on the variety class (days after planting, DAP): '
                .'extra-early hybrids about 75–90 days, early 90–100 days, intermediate 100–120 days, and late 120–140 days. '
                .'In much of Nigeria, common commercial hybrids reach physiological maturity (black layer / hard dough) around 90–120 DAP; '
                .'fresh “green maize” for roasting is often ready earlier, about 70–90 DAP when kernels are milky. '
                .'Dry grain is usually left to dry in the field a bit longer after physiological maturity so moisture falls toward ~13–14% for safe storage. '
                .'Check your seed label or IITA/NASC variety notes for the exact days-to-maturity of the cultivar you planted—altitude, rainfall, and fertility can shift harvest by 1–2 weeks.';
        }

        if ($this->mentions($text, ['cassava'])) {
            return 'Cassava is typically ready for fresh root harvest from about 8–12 months after planting for early varieties, and 12–18 months for many dual-purpose or late types. '
                .'Starch and dry-matter often peak later in the window; harvesting too early cuts yield, too late can raise fibre and lower quality for some markets. '
                .'Confirm the variety (e.g. TME / TMS lines) and your end use (fresh, gari, starch).';
        }

        if ($this->mentions($text, ['rice'])) {
            return 'Rice maturity is usually 90–150 days from sowing, by variety: early ~90–110 days, medium ~110–130 days, late ~130–150+ days. '
                .'Upland and lowland types differ; harvest when 80–85% of grains on the panicle are straw-coloured (physiological maturity), then dry to ~14% moisture for storage.';
        }

        if ($this->mentions($text, ['yam'])) {
            return 'White yam (Dioscorea rotundata) commonly takes about 7–10 months from planting to harvest, depending on sett size, variety, and rainfall. '
                .'Early harvest of ware yam is possible once vines senesce and tubers are well formed; seed yam systems may use shorter cycles.';
        }

        if ($this->mentions($text, ['broiler'])) {
            return 'Commercial broilers are typically marketed at about 5–7 weeks (35–49 days) depending on target live weight (often 1.8–2.5 kg), feed quality, and breed. '
                .'Slower dual-purpose birds take longer. Track weekly weight gain against the breed chart.';
        }

        if ($this->mentions($text, ['layer', 'pullet', 'egg'])) {
            return 'Point-of-lay for commercial layers is usually about 16–20 weeks of age (first eggs), with peak production around 24–30 weeks. '
                .'A commercial laying cycle is commonly managed for about 72–80 weeks of age before molt or cull decisions.';
        }

        if ($this->mentions($text, ['catfish', 'fish', 'tilapia', 'pond'])) {
            return 'Catfish grow-out from fingerling to market size (~1 kg) often takes about 4–6 months under good feeding and stocking; tilapia is commonly 4–6 months to plate size depending on strain and density. '
                .'Temperature, feed protein, and water quality change the calendar more than the calendar alone.';
        }

        if ($this->mentions($text, ['goat'])) {
            return 'Goats: meat kids are often marketed from about 6–12 months depending on breed and feeding; dairy goats kid after ~5 months gestation. Weaning is commonly 2–3 months. Your target market weight sets the calendar more than a single fixed age.';
        }

        if ($this->mentions($text, ['pig', 'swine'])) {
            return 'Pigs: commercial finishers often reach market weight around 5–7 months of age with good feed; gestation is about 114 days. Track average daily gain and feed conversion for your breed.';
        }

        return 'Maturity or market-ready time depends on the enterprise and variety. Name the crop or animal (maize, cassava, rice, broilers, layers, catfish, goats, pigs) and I will give the usual days or months.';
    }

    protected function pestsDiseasesListReply(string $text): string
    {
        if ($this->mentions($text, ['maize', 'corn'])) {
            return 'Common maize pests in Nigeria/West Africa: fall armyworm, stem borers, maize weevil (in store), aphids, and occasionally earworms. '
                .'Common diseases: Northern Corn Leaf Blight, gray leaf spot, maize streak virus, rusts, and ear/stalk rots in wet conditions. '
                .'Control—prevention first: resistant/tolerant hybrids, certified seed, correct spacing for airflow, early planting in your zone, and crop rotation. '
                .'Scout weekly from emergence. Fall armyworm/stem borers: crush egg masses, use Bt or approved insecticides at thresholds (follow the label). '
                .'Leaf blights: reduce leaf wetness, remove heavily infected residue, apply fungicides such as azoxystrobin or mancozeb early if pressure is high. '
                .'Storage weevils: dry grain to ~13–14% moisture and use clean hermetic bags. Always use NAFDAC-approved products and PPE.';
        }

        if ($this->mentions($text, ['cassava'])) {
            return 'Common cassava problems: cassava mosaic disease (CMD), cassava bacterial blight, mealybugs, green mites, and whiteflies (virus vectors). '
                .'Control: plant mosaic-free cuttings of resistant varieties, remove infected plants, avoid sharing diseased stems, and manage whiteflies/mealybugs with sanitation and approved insecticides only when needed. Good drainage limits root/stem rots.';
        }

        if ($this->mentions($text, ['rice'])) {
            return 'Common rice pests/diseases: blast, sheath blight, stem borers, rice bugs, and birds at maturity. '
                .'Control: resistant varieties, balanced nitrogen (excess worsens blast), good water management, remove infected debris, and use approved fungicides/insecticides at thresholds. Harvest promptly when grains are ready.';
        }

        if ($this->mentions($text, ['tomato', 'pepper', 'vegetable'])) {
            return 'Common vegetable pests/diseases: aphids, whiteflies, leaf miners, fruit worms, early/late blight, and bacterial wilts. '
                .'Control: resistant varieties where available, crop rotation, mulch, morning irrigation, remove infected leaves, yellow sticky traps, and label-approved fungicides/insecticides at first signs—not calendar spraying.';
        }

        if ($this->mentions($text, ['poultry', 'broiler', 'layer', 'chicken'])) {
            return 'Common poultry diseases: Newcastle disease, Gumboro (IBD), coccidiosis, fowl pox, and respiratory infections. '
                .'Control: vaccination schedule with your vet, dry litter, clean water, biosecurity (footbaths, limited visitors, all-in/all-out), and quarantine new birds. Antibiotics only under veterinary diagnosis.';
        }

        return 'Name the crop or animal (e.g. maize, cassava, rice, poultry) and I will list the most common pests/diseases and practical control steps for that enterprise only.';
    }

    protected function diseaseControlReply(string $text): string
    {
        if ($this->mentions($text, ['maize', 'corn'])) {
            return 'For maize disease control, combine prevention and targeted sprays: '
                .'use resistant/tolerant hybrids, rotate cereals with legumes, space plants for airflow, and avoid evening overhead irrigation that keeps leaves wet. '
                .'For fungal leaf diseases (e.g. Northern Corn Leaf Blight, gray leaf spot), registered strobilurin or triazole fungicides such as azoxystrobin, mancozeb, or propiconazole (follow the label) applied at early lesion stage help most. '
                .'For fall armyworm / stem borers use approved insecticides or biologicals (e.g. Bacillus thuringiensis products) at scouting thresholds—do not spray calendar-blind. '
                .'Always wear PPE, respect pre-harvest intervals, and buy NAFDAC/approved agrochemicals from trusted agro-dealers. Name the symptom (spots, worms, wilting) for a tighter pick.';
        }

        if ($this->mentions($text, ['cassava'])) {
            return 'Cassava disease control is mostly clean planting material and sanitation: use mosaic-free cuttings of resistant varieties, remove and destroy infected plants, control whiteflies (virus vectors) with yellow traps and approved insecticides only when needed, and avoid moving diseased stems between farms. '
                .'Fungal root/stem rots need well-drained ridges and avoiding waterlogged fields more than heavy spraying.';
        }

        if ($this->mentions($text, ['rice'])) {
            return 'Rice disease control: use certified seed, balanced nitrogen (excess N worsens blast), and good water management. '
                .'For blast / sheath blight, resistant varieties plus fungicides such as tricyclazole or azoxystrobin (label rates) at early infection help. '
                .'Remove infected debris and avoid dense, continuously wet canopies. Confirm the lesion type before buying a product.';
        }

        if ($this->mentions($text, ['layer', 'broiler', 'poultry', 'chicken', 'bird'])) {
            return 'Poultry disease control: vaccination schedule (Newcastle, Gumboro/IBD, fowl pox as advised by your vet), strict biosecurity (footbaths, limit visitors, all-in/all-out), dry litter, clean drinkers, and quarantine new birds. '
                .'Antibiotics are not routine “disease control”—use only under veterinary diagnosis. Disinfect houses between batches with approved farm disinfectants.';
        }

        if ($this->mentions($text, ['fish', 'catfish', 'tilapia', 'pond'])) {
            return 'Fish disease control: prioritize water quality (oxygen, ammonia, pH), avoid overcrowding and overfeeding, quarantine new stock, and remove dead fish promptly. '
                .'Salt baths or vet-prescribed treatments may help external parasites; antibiotics need laboratory/vet guidance. Probiotics and good feed often prevent more losses than drugs.';
        }

        return 'For farm disease control, start with prevention: resistant varieties or vaccinated stock, sanitation, correct spacing/stocking, and weekly scouting. '
            .'Crops: fungicides such as mancozeb, copper-based sprays, or azoxystrobin for fungal leaf spots/blights; insecticides or Bt products for chewing pests—only at thresholds, following the label and pre-harvest interval. '
            .'Poultry/fish: biosecurity, vaccines (birds), and water quality beat blanket drugs. '
            .'Buy NAFDAC-approved products from a trusted agro-dealer or vet. Tell me your enterprise (maize, cassava, rice, layers, broilers, catfish) and the symptom for exact product guidance.';
    }

    protected function soilTypeReply(string $text): string
    {
        if ($this->mentions($text, ['maize', 'corn'])) {
            return 'Best soils for maize are deep, well-drained loamy soils (sandy loam to clay loam) with good organic matter and a pH of about 5.5–7.0 (near neutral is ideal). '
                .'Maize likes soils that hold moisture but do not stay waterlogged—avoid heavy, poorly drained clay that ponds after rain, and very shallow or highly sandy soils unless you irrigate and fertilize carefully. '
                .'In Nigeria, fertile loams and well-managed savanna soils perform well; add compost/manure and correct acidity with lime where pH is low. '
                .'If your soil is sandy, use organic matter and split fertilizer; if clayey, ridge/raise beds for drainage.';
        }

        if ($this->mentions($text, ['cassava'])) {
            return 'Cassava prefers well-drained sandy loam to loamy soils and tolerates poorer fertility better than maize, but yields rise with organic matter. '
                .'Avoid waterlogged or heavy clay that stays wet—root rots increase. Plant on ridges/mounds where drainage is uncertain. Ideal pH is roughly 5.5–6.5.';
        }

        if ($this->mentions($text, ['rice'])) {
            return 'Lowland rice suits clay or clay-loam soils that can hold ponded water (puddled paddy). Upland rice needs well-drained loams more like maize. '
                .'Avoid soils that cannot retain a water layer for flooded systems. pH around 5.5–7.0 is generally workable.';
        }

        if ($this->mentions($text, ['yam'])) {
            return 'Yam does best on deep, friable, well-drained loamy soils rich in organic matter—mounds help where the topsoil is shallow. '
                .'Avoid waterlogged ground. Slightly acidic to neutral soils (about pH 5.5–6.5) are suitable.';
        }

        if ($this->mentions($text, ['tomato', 'pepper', 'vegetable'])) {
            return 'Vegetables generally need well-drained loamy soils high in organic matter, pH about 5.5–7.0. Raised beds help in wet seasons. Avoid compacted or saline soils.';
        }

        return 'Most crops prefer deep, well-drained loams with good organic matter and moderate pH (often ~5.5–7.0). '
            .'Name the crop (maize, cassava, rice, yam, vegetables) and I will give the best soil type for that crop only.';
    }

    protected function irrigationFrequencyReply(string $text): string
    {
        if ($this->mentions($text, ['maize', 'corn'])) {
            return 'If rainfall is little or none, water maize to keep the root zone moist—not flooded—about every 3–7 days depending on soil and heat: '
                .'sandy soils and hot dry weather often need water every 3–4 days; loams/clay loams may stretch to every 5–7 days. '
                .'Apply enough to wet roughly the top 30–45 cm of soil (about 25–40 mm per irrigation when rains fail), then wait until the top few centimetres begin to dry before the next cycle. '
                .'Priority stages: right after planting/emergence, knee-high to tasseling, and especially tasseling–silking through early grain fill—do not skip water then. '
                .'Irrigate early morning or late afternoon; avoid waterlogging. Mulch and good weed control help stretch each watering.';
        }

        if ($this->mentions($text, ['tomato', 'pepper', 'vegetable'])) {
            return 'With little rain, water vegetables lightly but often—often every 1–3 days on sandy beds, every 2–4 days on loams—keeping the root zone moist. Prefer morning irrigation and mulch. Avoid soaking foliage at night to cut disease.';
        }

        if ($this->mentions($text, ['rice'])) {
            return 'Lowland rice is managed by holding a shallow water layer when the system allows, not by “every X days” like upland crops. Upland rice with no rain needs frequent soil moisture checks and irrigation before the soil dries past the root zone, especially at tillering and flowering.';
        }

        if ($this->mentions($text, ['cassava'])) {
            return 'Cassava needs regular moisture mainly in the first 1–3 months after planting. With little rain, water every 5–10 days on light soils until established; mature cassava tolerates dry spells better. Avoid waterlogging.';
        }

        return 'Watering frequency depends on the crop, soil, and heat. Tell me the crop (e.g. maize) and I will give how often to irrigate when rains fail.';
    }

    protected function rainfallReply(string $text): string
    {
        if ($this->mentions($text, ['maize', 'corn'])) {
            return 'Maize needs about 500–800 mm of well-distributed rainfall over the growing season for good rainfed yields; many Nigerian hybrids perform best with roughly 600–900 mm if rains are even. '
                .'Critical moisture periods are germination/establishment, tasseling–silking, and early grain fill—drought then cuts yield sharply. '
                .'Less than ~400–500 mm without irrigation is risky except for extra-early varieties in short seasons. '
                .'Excess continuous waterlogging is also harmful. Where rains are unreliable, use early/extra-early hybrids, plant early in the local window, or irrigate at flowering if you can.';
        }

        if ($this->mentions($text, ['cassava'])) {
            return 'Cassava grows with about 1000–1500 mm annual rainfall in many humid zones, but established plants tolerate dry spells better than maize. '
                .'It needs enough early moisture for cutting establishment; prolonged waterlogging hurts roots. In drier belts, plant at rain onset and use moisture-conserving ridges/mulch.';
        }

        if ($this->mentions($text, ['rice'])) {
            return 'Rainfed upland rice often needs about 1000+ mm well distributed; lowland rice depends more on standing water in the field than on a single rainfall total. '
                .'Ensure reliable water at tillering and flowering. Drought at panicle initiation or flowering cuts yield badly.';
        }

        if ($this->mentions($text, ['yam'])) {
            return 'Yam generally needs a humid growing season with roughly 1000–1500 mm rainfall (or equivalent moisture), with a dry spell helpful toward harvest/curing. '
                .'Steady early rains after planting help vine and tuber set; waterlogging causes rot.';
        }

        return 'Rainfall needs differ by crop. Maize typically wants about 500–800 mm well distributed in the season; root crops and rice often need more or managed water. '
            .'Name the crop for an exact optimal rainfall range.';
    }

    protected function feedReply(string $text): string
    {
        if ($this->mentions($text, ['goat', 'sheep'])) {
            return 'For feeding goats (and sheep): base the ration on browse/forage—leaves, legumes, and clean cut grass—then add a small concentrate or crop by-product only if you need faster gain or milking. '
                .'Always provide clean water and a mineral salt lick. Avoid mouldy feed and sudden ration changes. Kids/lambs need access to forage early; wean around 2–3 months while watching body condition.';
        }

        if ($this->mentions($text, ['broiler'])) {
            return 'For broiler feeding: use a staged programme—starter (higher protein) → grower → finisher—matched to the breed chart. Feed ad lib with clean water always; never use mouldy mash. '
                .'In heat, feed more in cooler morning/evening hours. Track feed eaten vs weight gain (FCR); poor FCR usually means disease, heat stress, or wrong feed stage.';
        }

        if ($this->mentions($text, ['layer', 'pullet', 'egg'])) {
            return 'For layer feeding: grow pullets on developer feed, then switch to layer mash (~16–18% protein) at point-of-lay with free-choice calcium (oyster shell/grit). '
                .'Keep feed fresh and dry; egg drop often follows heat, poor feed, or water shortage before disease. Do not underfeed calcium in lay.';
        }

        if ($this->mentions($text, ['catfish', 'tilapia', 'fish', 'pond'])) {
            return 'For fish feeding: use quality floating pellets sized to the fish; feed 2–3 times daily to appetite (stop when most fish leave the surface) to avoid water pollution. '
                .'Overfeeding crashes oxygen overnight. Adjust amount as biomass grows; sort catfish by size so small fish can reach feed.';
        }

        if ($this->mentions($text, ['pig', 'swine'])) {
            return 'For pig feeding: match ration to stage (creep, weaner, grower, finisher/sow). Provide clean water always. Keep feed dry and mould-free. '
                .'Gestating sows need controlled energy to avoid fatness; lactating sows need higher intake. Track cost per kg gain.';
        }

        if ($this->mentions($text, ['cattle', 'cow', 'dairy'])) {
            return 'For cattle feeding: prioritize forage quality first, then add concentrate around milking or finishing goals. Provide mineral blocks and clean water. '
                .'Dairy cows need consistent daily intake; sudden feed changes cut milk. Match stocking rate to pasture so animals are not underfed.';
        }

        return 'Feeding depends on the animal. Name the species (goats, broilers, layers, catfish, pigs, cattle) and stage (young, grow-out, laying, milking) and I will give the exact ration approach.';
    }

    protected function storageReply(string $text): string
    {
        if ($this->mentions($text, ['maize', 'corn', 'grain', 'rice', 'sorghum', 'millet'])) {
            return 'To store maize/grain safely: dry to about 13–14% moisture before bagging, cool the grain, and use clean hermetic or tightly sealed bags in a dry, rodent-proof store. '
                .'Sort out damaged/mouldy grains; aflatoxin risk rises with moisture and insects. Do not store on damp floors. Check bags monthly for heat or insects.';
        }

        if ($this->mentions($text, ['cassava'])) {
            return 'Fresh cassava roots spoil quickly—process within 1–2 days or convert to chips/gari/flour. Dry chips thoroughly before bagging; never bag wet chips (mould and toxin risk). Store dried product cool and dry.';
        }

        if ($this->mentions($text, ['yam'])) {
            return 'Store yam in a cool, shaded, well-ventilated barn or crib; sort out bruised/rotten tubers first. Avoid stacking wet tubers. Check regularly and remove spoilage early.';
        }

        return 'For safe storage: dry produce to a safe moisture, use clean dry containers/bags, keep the store cool, ventilated, and rodent-proof, and sort damaged stock before storage. Name the crop for exact moisture and method.';
    }

    protected function fertilizerReply(string $text): string
    {
        if ($this->mentions($text, ['maize', 'corn'])) {
            return 'For maize fertilizer: a practical baseline on many Nigerian soils is NPK 15-15-15 (or similar) at planting—about 4–6 bags/ha depending on soil fertility—then urea or CAN as a top-dress at 3–4 weeks and again around tasseling. '
                .'Split nitrogen to reduce leaching. Add compost/manure if available. Soil-test when you can; sandy or heavily cropped fields often need the higher end of the range.';
        }

        if ($this->mentions($text, ['rice'])) {
            return 'For rice fertilizer: apply basal NPK at planting/transplanting, then split nitrogen top-dress at tillering and panicle initiation. Excess nitrogen without balanced K/P can worsen blast—do not overdose urea alone.';
        }

        if ($this->mentions($text, ['cassava'])) {
            return 'For cassava fertilizer: many fields respond to NPK at or soon after planting, especially on depleted soils; avoid waterlogged application. Organic manure on ridges helps establishment. Exact rates depend on soil—start modest and watch leaf colour and root set.';
        }

        if ($this->mentions($text, ['tomato', 'pepper', 'vegetable'])) {
            return 'For vegetables: use well-rotted compost/manure in beds, then a balanced NPK; side-dress lightly during fruiting. Avoid raw poultry litter on leafy beds. Keep nitrogen moderate on tomatoes to reduce soft growth and disease.';
        }

        return 'For fertilizer choice: soil-test if possible. Cereals generally need basal NPK at planting plus split nitrogen top-dress; pair with compost or well-rotted manure. '
                .'Water-in fertilizer on dry soils and avoid burning seedlings. Name your crop (maize, rice, cassava, vegetables) for a crop-specific rate plan—baseline for maize is basal NPK then urea/CAN top-dress.';
    }

    protected function spacingReply(string $text): string
    {
        if ($this->mentions($text, ['maize', 'corn'])) {
            return 'For maize spacing, a common recommendation is about 75 cm between rows and 25 cm between plants in the row (roughly 53,000 plants/ha with one seed per stand), or follow your hybrid’s seed-company leaflet. '
                .'Wider spacing improves airflow and can cut leaf disease; overcrowding raises lodging and barren stalks when fertility or water is low.';
        }

        if ($this->mentions($text, ['cassava'])) {
            return 'For cassava, typical spacing is about 1 m × 1 m on ridges/mounds (about 10,000 stands/ha), adjusted by variety and whether you intercrop. Give enough room for canopy and root expansion.';
        }

        if ($this->mentions($text, ['rice'])) {
            return 'For transplanted rice, common spacing is about 20 × 20 cm (or 20 × 15 cm for some systems). Direct-seeded rice follows seed rate on the variety sheet more than fixed hills.';
        }

        return 'Spacing depends on the crop and variety. Tell me which crop you are planting (maize, cassava, rice, etc.) and I will give the usual row and plant distances.';
    }

    protected function howToPlantReply(string $text): string
    {
        if ($this->mentions($text, ['maize', 'corn'])) {
            return 'To plant maize: clear/land-prep, plant certified seed 3–5 cm deep when soil moisture is reliable, at about 75 × 25 cm spacing, one seed per hole (or thin to one). '
                .'Apply basal NPK at planting, weed early (first 4–6 weeks), and plan nitrogen top-dress at 3–4 weeks. Scout for fall armyworm from emergence.';
        }

        if ($this->mentions($text, ['cassava'])) {
            return 'To plant cassava: use mosaic-free stem cuttings (about 20–25 cm) of an improved variety; plant on ridges or mounds in drained soil at ~1 m spacing, at the start of reliable rains. Weed heavily for the first 3 months.';
        }

        if ($this->mentions($text, ['yam'])) {
            return 'To plant yam: use healthy setts, plant at rain onset on mounds/ridges, mulch, and stake where vines need support. Keep weed-free early in the season.';
        }

        return 'To plant successfully: use clean certified seed or planting material, wait for reliable moisture (or irrigate), follow crop-specific spacing and depth, apply basal nutrition, and weed early. Name the crop for step-by-step spacing and depth.';
    }

    protected function startEnterpriseReply(string $text): string
    {
        if ($this->mentions($text, ['catfish', 'tilapia', 'fish', 'pond'])) {
            return 'To start a small catfish pond: secure a site with reliable water, build or line a pond you can drain, stock quality fingerlings (not random wild fish), and budget for quality feed—feed is usually your largest cost. '
                .'Start at a stocking density you can feed and manage; feed 2–3 times daily to appetite; check oxygen/stress at dawn; sort sizes to reduce cannibalism. Keep simple records of stock, feed, and mortality from day one.';
        }

        if ($this->mentions($text, ['broiler', 'poultry', 'chicken'])) {
            return 'To start broilers: prepare a clean, ventilated house, buy quality day-old chicks, follow starter→grower→finisher feed, and run vaccinations with vet advice. '
                .'Start with a flock size you can fund through 6–7 weeks (feed + chicks + litter). Biosecurity first—limit visitors and disinfect between batches.';
        }

        if ($this->mentions($text, ['layer', 'egg'])) {
            return 'To start layers: plan housing, lighting, and a feed budget through point-of-lay (~16–20 weeks) before eggs pay back. Buy quality pullets or day-olds, vaccinate on schedule, and switch to layer mash plus calcium at lay.';
        }

        if ($this->mentions($text, ['maize', 'corn', 'crop', 'cassava', 'rice'])) {
            return 'To start a crop enterprise: pick one crop with a known local market, confirm land and labour for weeding/harvest, buy certified seed, plant in the correct seasonal window, and write a simple budget (seed, fertilizer, labour, transport). '
                .'Start on an area you can weed on time—late weeding kills yield more often than seed choice alone.';
        }

        return 'To start a farm enterprise: choose one activity (crop, broilers, layers, or fish), confirm land/water/market, budget the full first cycle, and start small enough to fund feed or inputs to the end. Name the enterprise for a setup checklist.';
    }

    protected function composeFromKnowledge(string $text, string $name): string
    {
        $ranked = $this->rankArticles($text);

        if ($ranked === [] || ($ranked[0]['score'] ?? 0) < 1) {
            return $this->specificClarifyingAnswer($text, $name);
        }

        $best = $ranked[0]['article'];
        $subject = $this->detectSubjectLabel($text) ?? Str::lower($best['title']);
        $focused = $this->extractRelevantSentences($best['body'], $text);

        return "On your question about {$subject}: {$focused}";
    }

    /**
     * Keep only sentences that best match this question (avoids dumping a full topic overview).
     */
    protected function extractRelevantSentences(string $body, string $text): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', trim($body)) ?: [];
        $sentences = array_values(array_filter(array_map('trim', $sentences)));

        if ($sentences === []) {
            return $body;
        }

        $terms = $this->questionTerms($text);
        $scored = [];

        foreach ($sentences as $index => $sentence) {
            $score = 0;
            $lower = Str::lower($sentence);
            foreach ($terms as $term) {
                if (Str::contains($lower, $term)) {
                    $score += 2;
                }
            }
            // Slight preference for earlier practical sentences when scores tie.
            $scored[] = ['score' => $score, 'index' => $index, 'sentence' => $sentence];
        }

        usort($scored, function (array $a, array $b): int {
            if ($a['score'] === $b['score']) {
                return $a['index'] <=> $b['index'];
            }

            return $b['score'] <=> $a['score'];
        });

        $picked = [];
        foreach ($scored as $row) {
            if ($row['score'] > 0 || count($picked) === 0) {
                $picked[] = $row;
            }
            if (count($picked) >= 2) {
                break;
            }
        }

        // If nothing matched terms, use the first two sentences only (still focused).
        if (($picked[0]['score'] ?? 0) === 0) {
            $picked = [];
            foreach (array_slice($sentences, 0, 2) as $index => $sentence) {
                $picked[] = ['sentence' => $sentence, 'index' => $index];
            }
        }

        usort($picked, fn (array $a, array $b): int => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        return trim(implode(' ', array_column($picked, 'sentence')));
    }

    /**
     * @return list<string>
     */
    protected function questionTerms(string $text): array
    {
        $stop = ['the', 'and', 'for', 'with', 'what', 'when', 'where', 'which', 'how', 'do', 'does', 'can', 'i', 'my', 'a', 'an', 'to', 'of', 'in', 'on', 'is', 'are', 'should', 'would', 'please', 'tell', 'me', 'about'];

        $words = preg_split('/[^a-z0-9]+/', $text) ?: [];
        $terms = [];

        foreach ($words as $word) {
            if (Str::length($word) < 3 || in_array($word, $stop, true)) {
                continue;
            }
            $terms[] = $word;
        }

        return array_values(array_unique($terms));
    }

    protected function detectSubjectLabel(string $text): ?string
    {
        foreach ([
            'maize' => ['maize', 'corn'],
            'cassava' => ['cassava'],
            'rice' => ['rice'],
            'yam' => ['yam'],
            'broilers' => ['broiler'],
            'layers' => ['layer', 'pullet'],
            'goats' => ['goat'],
            'sheep' => ['sheep'],
            'pigs' => ['pig', 'swine'],
            'cattle' => ['cattle', 'cow', 'dairy'],
            'catfish' => ['catfish'],
            'fish farming' => ['tilapia', 'fish', 'pond'],
            'soil' => ['soil'],
            'fertilizer' => ['fertilizer', 'fertiliser', 'npk'],
            'irrigation' => ['irrigat', 'water'],
            'storage' => ['storage', 'store'],
        ] as $label => $needles) {
            if ($this->mentions($text, $needles)) {
                return $label;
            }
        }

        return null;
    }

    protected function specificClarifyingAnswer(string $text, string $name): string
    {
        $subject = $this->detectSubjectLabel($text);

        if ($subject !== null) {
            return "I want to answer your exact question about {$subject}, {$name}. "
                .'Ask one focused point—for example timing, spacing, fertilizer rate, feeding, disease symptoms, storage, or how to start—and I will reply to that point only with practical steps.';
        }

        return "Ask one specific farming question, {$name}—name the crop or animal and what you need (e.g. “best time to plant maize in Kaduna”, “how to feed goats”, “fertilizer for maize”). "
            .'I answer that individual question directly rather than giving a general farm overview.';
    }

    /**
     * @return list<array{score: int, article: array{id: string, keywords: list<string>, title: string, body: string}}>
     */
    protected function rankArticles(string $text): array
    {
        $ranked = [];
        $intentBoosts = $this->intentBoosts($text);

        foreach ($this->knowledge->articles() as $article) {
            $score = 0;
            foreach ($article['keywords'] as $keyword) {
                $needle = Str::lower($keyword);
                if ($needle !== '' && Str::contains($text, $needle)) {
                    $score += max(1, (int) round(Str::length($needle) / 4));
                }
            }

            $score += $intentBoosts[$article['id']] ?? 0;

            if ($score > 0) {
                $ranked[] = ['score' => $score, 'article' => $article];
            }
        }

        usort($ranked, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return $ranked;
    }

    /**
     * Boost topic articles when the question intent is clear (e.g. store/feed/start).
     *
     * @return array<string, int>
     */
    protected function intentBoosts(string $text): array
    {
        $boosts = [];

        if ($this->isPlantingTimeQuestion($text)) {
            $boosts['planting-season'] = 12;
        }

        if ($this->isSoilTypeQuestion($text)) {
            $boosts['maize-soil'] = 12;
            $boosts['soil'] = 8;
        }

        if ($this->isRainfallQuestion($text)) {
            $boosts['maize-rainfall'] = 12;
            $boosts['water-irrigation'] = 6;
        }

        if ($this->isPestsDiseasesListQuestion($text)) {
            $boosts['maize-pests-diseases'] = 14;
            $boosts['pest-control'] = 10;
            $boosts['disease-control'] = 10;
        }

        if ($this->mentions($text, ['store', 'storage', 'silo', 'bagging', 'hermetic', 'aflatoxin', 'postharvest', 'post-harvest', 'dry grain', 'safely'])) {
            $boosts['storage-postharvest'] = 10;
        }

        if ($this->mentions($text, ['feed', 'feeding', 'ration', 'forage', 'what to feed'])) {
            $boosts['feed'] = 6;
        }

        if ($this->mentions($text, ['start', 'beginner', 'how do i start', 'new farm', 'begin a'])) {
            $boosts['start-farming'] = 5;
        }

        if ($this->mentions($text, ['profit', 'cost', 'budget', 'price', 'loan', 'business', 'sell'])) {
            $boosts['farm-business'] = 5;
        }

        if ($this->mentions($text, ['vaccin', 'newcastle', 'gumboro', 'biosecurity', 'quarantine'])) {
            $boosts['poultry-health'] = 5;
            $boosts['biosecurity'] = 4;
        }

        if ($this->mentions($text, ['weed', 'armyworm', 'insect', 'pest spray', 'herbicide'])) {
            $boosts['pest-control'] = 6;
        }

        // Enterprise boosts so species context is not drowned by generic intents.
        foreach ([
            'maize-overview' => ['maize', 'corn'],
            'cassava-overview' => ['cassava'],
            'rice-overview' => ['rice', 'paddy'],
            'yam-overview' => ['yam'],
            'broilers' => ['broiler'],
            'layers' => ['layer', 'pullet', 'egg'],
            'catfish-tilapia' => ['catfish', 'tilapia', 'fish', 'pond'],
            'goats-sheep' => ['goat', 'sheep'],
            'pigs' => ['pig', 'swine'],
            'cattle' => ['cattle', 'cow', 'dairy'],
            'tomato-pepper' => ['tomato', 'pepper', 'vegetable'],
            'fertilizer' => ['fertilizer', 'fertiliser', 'npk', 'urea', 'compost', 'manure'],
            'soil' => ['soil', 'lime', 'erosion'],
            'water-irrigation' => ['irrigat', 'drought', 'drainage'],
        ] as $id => $needles) {
            if ($this->mentions($text, $needles)) {
                $boosts[$id] = ($boosts[$id] ?? 0) + 7;
            }
        }

        return $boosts;
    }

    protected function isSoilTypeQuestion(string $text): bool
    {
        return $this->mentions($text, [
            'type of soil', 'soil type', 'best soil', 'which soil', 'what soil',
            'suitable soil', 'ideal soil', 'soil for', 'soil is best', 'best for maize cultivation',
            'loamy', 'sandy soil', 'clay soil', 'soil suitable',
        ]) || (
            $this->mentions($text, ['soil'])
            && $this->mentions($text, ['best', 'which', 'what', 'suitable', 'ideal', 'type', 'good for', 'cultivation'])
        );
    }

    protected function isIrrigationFrequencyQuestion(string $text): bool
    {
        return $this->mentions($text, [
            'how often', 'how frequently', 'watering schedule', 'irrigation schedule',
            'how often should', 'how often do i', 'how often can i',
            'should be watered', 'should i water', 'when to water', 'when should i water',
            'how to water', 'irrigate if', 'watered if', 'watering if',
            'little or no rainfall', 'little rainfall', 'no rainfall', 'no rain',
            'without rain', 'when there is no rain', 'if there is little',
        ]) || (
            $this->mentions($text, ['water', 'watered', 'watering', 'irrigat'])
            && $this->mentions($text, ['often', 'frequency', 'schedule', 'every', 'days', 'daily'])
        );
    }

    protected function isRainfallQuestion(string $text): bool
    {
        if ($this->isIrrigationFrequencyQuestion($text)) {
            return false;
        }

        return $this->mentions($text, [
            'rainfall', 'how much rain', 'how much rainfall', 'rain does', 'mm of rain',
            'water requirement', 'water requirements', 'how much water', 'precipitation',
            'rain for optimal', 'rain needed', 'needs for optimal growth',
        ]) || (
            $this->mentions($text, ['rain', 'rainfall', 'mm'])
            && $this->mentions($text, ['how much', 'optimal', 'need', 'require', 'enough'])
        );
    }

    protected function isFeedQuestion(string $text): bool
    {
        return $this->mentions($text, [
            'feed', 'feeding', 'what to feed', 'how to feed', 'ration', 'what should i feed',
        ]);
    }

    protected function isStorageQuestion(string $text): bool
    {
        return $this->mentions($text, [
            'store', 'storage', 'how to store', 'safely store', 'preserve grain', 'hermetic',
            'postharvest', 'post-harvest',
        ]);
    }

    protected function isFertilizerQuestion(string $text): bool
    {
        return $this->mentions($text, [
            'fertilizer', 'fertiliser', 'npk', 'urea', 'what fertilizer', 'which fertilizer',
            'manure for', 'compost for', 'top dress', 'topdress',
        ]);
    }

    protected function isSpacingQuestion(string $text): bool
    {
        return $this->mentions($text, [
            'spacing', 'how far apart', 'plant distance', 'distance between', 'population per',
        ]);
    }

    protected function isHowToPlantQuestion(string $text): bool
    {
        if ($this->isPlantingTimeQuestion($text)) {
            return false;
        }

        return $this->mentions($text, [
            'how to plant', 'how do i plant', 'how can i plant', 'planting method',
            'how to sow', 'steps to plant',
        ]);
    }

    protected function isStartEnterpriseQuestion(string $text): bool
    {
        return $this->mentions($text, [
            'how do i start', 'how to start', 'how can i start', 'starting a', 'start a small',
            'begin a', 'set up a', 'setup a',
        ]);
    }

    protected function looksNonFarming(string $text): bool
    {
        $farmSignals = [
            'farm', 'crop', 'maize', 'corn', 'cassava', 'rice', 'yam', 'soil', 'fertilizer', 'fertiliser',
            'plant', 'harvest', 'poultry', 'chicken', 'broiler', 'layer', 'egg', 'goat', 'pig', 'cattle',
            'cow', 'fish', 'pond', 'catfish', 'tilapia', 'irrigat', 'pest', 'disease', 'weed', 'feed',
            'livestock', 'agric', 'agro', 'manure', 'compost', 'seed', 'tractor', 'barn', 'pen',
            'vaccine', 'hatch', 'fingerling', 'garden', 'vegetable', 'tomato', 'pepper', 'cocoa',
            'palm', 'storage', 'aflatoxin', 'extension', 'herd', 'flock', 'acre', 'hectare', 'farming',
            'farmer', 'cultivate', 'livestock', 'snail', 'rabbit', 'sheep', 'dairy', 'beef',
        ];

        if ($this->mentions($text, $farmSignals)) {
            return false;
        }

        // Short or vague questions inside CyraAI are treated as farm context.
        if (Str::wordCount($text) <= 12) {
            return false;
        }

        $nonFarm = [
            'bitcoin', 'crypto', 'football', 'movie', 'dating', 'politics election',
            'write python', 'javascript code', 'homework math',
        ];

        return $this->mentions($text, $nonFarm);
    }

    protected function isMaturityQuestion(string $text): bool
    {
        if ($this->isPlantingTimeQuestion($text)) {
            return false;
        }

        return $this->mentions($text, [
            'matur', 'how long', 'how many days', 'how many months', 'days to', 'months to',
            'time to harvest', 'when to harvest', 'ready to harvest', 'harvest time',
            'grow cycle', 'growth cycle', 'gestation', 'days after planting', 'dap',
            'fully grown', 'fully grow', 'reach harvest', 'until harvest', 'to be ready',
            'market size', 'market weight', 'point of lay',
        ]);
    }

    protected function isPlantingTimeQuestion(string $text): bool
    {
        return $this->mentions($text, [
            'best time to plant',
            'best planting time',
            'best planting',
            'time to plant',
            'when to plant',
            'when should i plant',
            'when do i plant',
            'when can i plant',
            'planting time',
            'planting season',
            'planting window',
            'sowing time',
            'when to sow',
            'ideal time to plant',
            'right time to plant',
            'season to plant',
        ]);
    }

    protected function isPestsDiseasesListQuestion(string $text): bool
    {
        return $this->mentions($text, [
            'pests and diseases', 'pest and disease', 'pests & diseases',
            'common pests', 'common diseases', 'major pests', 'major diseases',
            'diseases affecting', 'pests affecting', 'pests of', 'diseases of',
            'how can they be controlled', 'how are they controlled',
            'how to control them', 'control them',
        ]) || (
            $this->mentions($text, ['pest', 'pests', 'disease', 'diseases'])
            && $this->mentions($text, ['common', 'most', 'affecting', 'control', 'controlled', 'manage', 'management'])
        );
    }

    protected function isDiseaseControlQuestion(string $text): bool
    {
        if ($this->isPestsDiseasesListQuestion($text)) {
            return false;
        }

        return $this->mentions($text, [
            'disease control', 'pest control', 'disease management', 'pest management',
            'fungicide', 'insecticide', 'pesticide', 'agrochemical',
            'what can i use for disease', 'what can i use against',
            'how to control disease', 'how to control pest', 'control disease',
            'control the disease', 'treat the disease', 'treatment for disease',
            'spray for disease', 'what spray', 'what chemical', 'ipm',
        ]);
    }

    /**
     * @param  list<string>  $needles
     */
    protected function mentions(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && Str::contains($haystack, Str::lower($needle))) {
                return true;
            }
        }

        return false;
    }
}
