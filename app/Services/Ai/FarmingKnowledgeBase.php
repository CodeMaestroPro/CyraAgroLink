<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Curated West Africa–oriented farming knowledge used by CyraAI offline.
 *
 * Each entry: id, keywords (scored match), title, body.
 */
class FarmingKnowledgeBase
{
    /**
     * @return list<array{id: string, keywords: list<string>, title: string, body: string}>
     */
    public function articles(): array
    {
        return [
            [
                'id' => 'maize-overview',
                'keywords' => ['maize', 'corn', 'zea', 'cob', 'tassel'],
                'title' => 'Maize',
                'body' => 'Use certified seed suited to your zone; common spacing ~75 × 25 cm. Best soils are deep, well-drained loams (pH about 5.5–7.0). Maize typically needs about 500–800 mm well-distributed seasonal rainfall (critical at flowering). Many Nigerian hybrids mature physiologically at about 90–120 days after planting (earlier for extra-early; green maize often 70–90 days). Weed the first 4–6 weeks. Scout for fall armyworm, stem borers, and leaf blights. Split nitrogen: basal NPK then urea/CAN top-dress.',
            ],
            [
                'id' => 'maize-pests-diseases',
                'keywords' => ['pest', 'pests', 'disease', 'diseases', 'armyworm', 'borer', 'blight', 'weevil', 'streak'],
                'title' => 'Maize pests and diseases',
                'body' => 'Major maize pests include fall armyworm, stem borers, and storage weevils; major diseases include Northern Corn Leaf Blight, gray leaf spot, maize streak virus, and ear rots. Control with resistant hybrids, scouting, Bt or approved insecticides at thresholds, early fungicide if blight pressure rises, residue sanitation, and dry hermetic storage for grain.',
            ],
            [
                'id' => 'maize-soil',
                'keywords' => ['soil for maize', 'maize soil', 'soil type', 'loam', 'best soil'],
                'title' => 'Maize soil',
                'body' => 'Maize performs best on deep, well-drained sandy-loam to clay-loam soils with organic matter and pH about 5.5–7.0. Avoid waterlogged heavy clays and very shallow sands unless you irrigate and build organic matter.',
            ],
            [
                'id' => 'maize-rainfall',
                'keywords' => ['rainfall', 'rain for maize', 'maize water', 'mm', 'precipitation'],
                'title' => 'Maize rainfall',
                'body' => 'Optimal rainfed maize usually needs about 500–800 mm of well-distributed rain in the season; flowering and early grain fill are the most drought-sensitive stages. Extra-early varieties help where the rainy season is short.',
            ],
            [
                'id' => 'cassava-overview',
                'keywords' => ['cassava', 'manioc', 'garri', 'gari', 'fufu'],
                'title' => 'Cassava',
                'body' => 'Plant mosaic-free cuttings of improved varieties on ridges/mounds in drained soil. Weed heavily in the first 3 months. Roots are often ready from 8–12 months (early) to 12–18 months (late/dual-purpose). Keep stems clean between farms to limit CMD (mosaic) and whiteflies.',
            ],
            [
                'id' => 'rice-overview',
                'keywords' => ['rice', 'paddy', 'upland rice', 'lowland rice'],
                'title' => 'Rice',
                'body' => 'Level fields for even water; use clean seed. Maturity is commonly 90–150 days by variety. Harvest when ~80–85% of grains are straw-coloured; dry to ~14% moisture. Excess nitrogen and continuous leaf wetness raise blast risk—use resistant varieties and approved fungicides when needed.',
            ],
            [
                'id' => 'yam-overview',
                'keywords' => ['yam', 'dioscorea', 'sett'],
                'title' => 'Yam',
                'body' => 'Plant healthy setts at reliable rains; stake where vines need support. Ware yam often needs about 7–10 months. Keep weed-free early; store in cool, ventilated barns and sort out rotten tubers quickly.',
            ],
            [
                'id' => 'tomato-pepper',
                'keywords' => ['tomato', 'pepper', 'garden egg', 'vegetable', 'okra', 'ugu', 'leafy'],
                'title' => 'Vegetables',
                'body' => 'Use raised beds or well-drained plots, mulch to hold moisture, and stake tomatoes. Scout daily in humid weather for blight, leaf miners, and whiteflies. Prefer morning irrigation; avoid wetting foliage at night. Rotate beds and destroy crop residue after harvest.',
            ],
            [
                'id' => 'cocoa-oil-palm',
                'keywords' => ['cocoa', 'cacao', 'oil palm', 'palm oil', 'plantation'],
                'title' => 'Tree crops',
                'body' => 'Cocoa and oil palm need good shade/management plans, clean planting material, and regular sanitation (remove diseased pods/fronds). Fertilize by soil/leaf guidance; control black pod (cocoa) with sanitation plus copper fungicides where recommended. Harvest ripe fruit on schedule to cut losses.',
            ],
            [
                'id' => 'legumes-cereals',
                'keywords' => ['groundnut', 'peanut', 'soybean', 'soya', 'sorghum', 'millet', 'cowpea', 'beans'],
                'title' => 'Legumes & cereals',
                'body' => 'Plant on time for your rainfall belt; inoculate soybeans when possible. Groundnut and cowpea fix nitrogen—great in rotation with maize. Dry grain thoroughly before bagging; aflatoxin risk rises with moisture and insect damage—use clean, dry stores and hermetic bags where available.',
            ],
            [
                'id' => 'broilers',
                'keywords' => ['broiler', 'broilers', 'meat bird', 'chicken meat'],
                'title' => 'Broilers',
                'body' => 'Market age is often 5–7 weeks (35–49 days) depending on target weight and feed. Follow starter→grower→finisher feeds, keep litter dry, ventilate well, and run a vaccination schedule with your vet. Biosecurity first—limit visitors, footbaths, and separate ages. In heat: cool drinking water, shade, lower stocking density, and avoid midday disturbance.',
            ],
            [
                'id' => 'layers',
                'keywords' => ['layer', 'layers', 'egg', 'eggs', 'pullet', 'point of lay'],
                'title' => 'Layers',
                'body' => 'Point-of-lay is commonly 16–20 weeks; give 14–16 hours light once in lay, layer mash (~16–18% protein) plus calcium (oyster shell/grit). Track hen-day production; cull chronic non-layers. Heat stress: cool water, shade, lower density. Vaccinate on a vet schedule.',
            ],
            [
                'id' => 'poultry-health',
                'keywords' => ['newcastle', 'gumboro', 'ibd', 'fowl pox', 'poultry disease', 'bird flu', 'avian', 'coccidiosis'],
                'title' => 'Poultry health',
                'body' => 'Core controls: vaccination (Newcastle, Gumboro/IBD, fowl pox as advised), dry litter, clean water, quarantine new birds, and isolate sick ones. Sudden high mortality needs urgent vet attention. Do not use antibiotics as routine “growth” or blanket disease control.',
            ],
            [
                'id' => 'catfish-tilapia',
                'keywords' => ['catfish', 'tilapia', 'fish', 'pond', 'aquaculture', 'fingerling', 'hatchery'],
                'title' => 'Fish farming',
                'body' => 'Stock quality fingerlings; feed to appetite in 2–3 sessions; avoid overcrowding. Check dissolved oxygen (stress often shows at dawn). Catfish to ~1 kg often takes ~4–6 months with good feed; sort sizes to cut cannibalism. Partial water exchange and remove mortalities fast. Quarantine new stock.',
            ],
            [
                'id' => 'goats-sheep',
                'keywords' => ['goat', 'goats', 'sheep', 'ram', 'doe', 'buck', 'small ruminant'],
                'title' => 'Goats & sheep',
                'body' => 'Provide browse/forage plus mineral salt; clean housing raised off damp ground; deworm on a vet/fecal plan (avoid blind monthly dosing). Vaccinate for PPR where recommended. Separate sick animals; control lice/mange early. Breeding: keep records of kidding and weaning weights.',
            ],
            [
                'id' => 'pigs',
                'keywords' => ['pig', 'pigs', 'swine', 'sow', 'boar', 'piglet'],
                'title' => 'Pigs',
                'body' => 'Use balanced feed by stage (creep, weaner, grower, finisher), clean water always, and dry pens. Vaccinate/deworm per vet advice; biosecurity matters (limit visitors, footbaths). Sows need body-condition management before breeding. Market weights depend on breed and feed cost—track FCR.',
            ],
            [
                'id' => 'cattle',
                'keywords' => ['cattle', 'cow', 'cows', 'bull', 'dairy', 'beef', 'herd'],
                'title' => 'Cattle',
                'body' => 'Match stocking to pasture; provide mineral blocks and clean water. Vaccinate and control ticks/tsetse risks by zone with vet guidance. Dairy cows need consistent forage + concentrate around milking; keep milking hygiene high to prevent mastitis. Record births, treatments, and milk/sale weights.',
            ],
            [
                'id' => 'rabbits-snails',
                'keywords' => ['rabbit', 'rabbits', 'snail', 'snails', 'heliculture'],
                'title' => 'Rabbits & snails',
                'body' => 'Rabbits: cool, clean hutches, quality pellets/forage, protect from dogs/heat; watch for diarrhoea and mange. Snails: shaded pens, moist clean substrate, calcium sources, and secure fencing against predators. Buy stock from healthy farms and quarantine newcomers.',
            ],
            [
                'id' => 'fertilizer',
                'keywords' => ['fertilizer', 'fertiliser', 'npk', 'urea', 'can', 'manure', 'compost', 'top dress', 'topdress'],
                'title' => 'Fertilizer & organic matter',
                'body' => 'Soil-test when you can. Cereals: basal NPK at planting, split nitrogen top-dress. Compost or well-rotted manure builds structure; compost poultry litter before heavy vegetable use. Water-in fertilizer on dry soils; avoid burning seedlings. Organic matter + correct pH beats one-off “miracle” products.',
            ],
            [
                'id' => 'soil',
                'keywords' => ['soil', 'ph', 'erosion', 'organic matter', 'lime', 'laterite', 'clay', 'loam'],
                'title' => 'Soil health',
                'body' => 'Build organic matter (compost, manure, cover crops), protect soil with mulch/residue, and correct acidity with agricultural lime where needed. Avoid burning fields and continuous deep tillage on fragile soils. Rotate crops and integrate livestock waste carefully.',
            ],
            [
                'id' => 'water-irrigation',
                'keywords' => ['water', 'irrigation', 'irrigate', 'drought', 'flood', 'drainage', 'borehole', 'drip'],
                'title' => 'Water & irrigation',
                'body' => 'Irrigate to root depth rather than frequent shallow sprinkles; prefer morning irrigation for leafy crops to cut overnight wetness/disease. Mulch reduces evaporation. Maintain drains before peak rains. Ponds: balance exchange with biosecurity—disinfect shared nets/equipment.',
            ],
            [
                'id' => 'disease-control',
                'keywords' => ['disease', 'fungicide', 'blight', 'mosaic', 'rot', 'wilting', 'pathogen', 'infection'],
                'title' => 'Crop disease control',
                'body' => 'Prevention first: resistant varieties, clean seed/cuttings, spacing, sanitation, and scouting. Fungal leaf diseases often respond to timely mancozeb, copper, or strobilurins/triazoles (e.g. azoxystrobin)—always follow the label and pre-harvest interval. Buy NAFDAC-approved products. Name the crop and symptom for a tighter pick.',
            ],
            [
                'id' => 'pest-control',
                'keywords' => ['pest', 'insect', 'insecticide', 'armyworm', 'borer', 'aphid', 'whitefly', 'weevil', 'nematode', 'herbicide', 'weed'],
                'title' => 'Pests & weeds',
                'body' => 'Scout weekly; spray only at thresholds. Fall armyworm: early detection, hand-crush egg masses, Bt or approved insecticides. Weeds: early weeding or safe herbicides with correct nozzle/timing—protect non-target crops. Rotate modes of action to slow resistance. PPE always.',
            ],
            [
                'id' => 'storage-postharvest',
                'keywords' => ['storage', 'store', 'postharvest', 'post-harvest', 'silo', 'bag', 'hermetic', 'aflatoxin', 'spoilage', 'warehouse', 'grain', 'dry grain', 'safely'],
                'title' => 'Storage & post-harvest',
                'body' => 'Dry grain to safe moisture (~13–14% for many cereals) before bagging. Use clean, sealed bags or hermetic storage; keep stores cool, dry, and rodent-proof. Sort damaged produce; never store wet cassava chips or mouldy grain for food/feed—aflatoxin risk. For perishables, shade, vent, and move to market fast.',
            ],
            [
                'id' => 'feed',
                'keywords' => ['feed', 'feeding', 'ration', 'concentrate', 'forage', 'silage', 'mash', 'pellet'],
                'title' => 'Animal feed',
                'body' => 'Match feed to species and stage (starter/grower/finisher or layer mash). Keep feed dry and mould-free; mouldy feed can kill poultry/fish. For ruminants, prioritize forage quality then add concentrate. Track feed cost per kg gain or per egg—feed is usually the largest expense.',
            ],
            [
                'id' => 'planting-season',
                'keywords' => ['planting time', 'when to plant', 'planting season', 'sowing', 'season'],
                'title' => 'Planting windows',
                'body' => 'Plant after reliable moisture (or when irrigation is ready), matching variety to your agro-ecology. Southern zones often start earlier than the far north. Stagger plantings when labour and cashflow allow so you are not forced to sell everything at once.',
            ],
            [
                'id' => 'farm-business',
                'keywords' => ['profit', 'cost', 'budget', 'record', 'business', 'loan', 'invest', 'market price', 'sell', 'offtake', 'coop'],
                'title' => 'Farm business',
                'body' => 'Write simple records: inputs, labour, feed, sales, and mortality. Know your cost per unit (bag, bird, kg fish) before pricing. Diversify markets (spot, contract, coop). Treat farming as a business—cashflow timing matters as much as yield.',
            ],
            [
                'id' => 'climate-risk',
                'keywords' => ['climate', 'rain', 'heat', 'harmattan', 'weather', 'flooding', 'dry spell'],
                'title' => 'Climate & risk',
                'body' => 'Use early/extra-early varieties where seasons are short; keep drainage ready for floods; mulch and irrigate strategically in dry spells. Shade and cool water protect poultry in heat. Diversify enterprises so one weather shock does not wipe the farm.',
            ],
            [
                'id' => 'mechanization-labour',
                'keywords' => ['tractor', 'mechanization', 'labour', 'hire', 'equipment', 'planter', 'sheller'],
                'title' => 'Labour & machines',
                'body' => 'Hire tractors/planters when timely planting beats hand labour cost. Maintain equipment; share or hire for peak windows. Plan labour for weeding and harvest peaks—late weeding is a common yield killer.',
            ],
            [
                'id' => 'start-farming',
                'keywords' => ['start', 'begin', 'how do i start', 'new farm', 'beginner', 'first time'],
                'title' => 'Starting a farm',
                'body' => 'Pick one enterprise you can manage (e.g. maize, broilers, or catfish), confirm land/water/market access, write a simple budget, and start at a size you can fund through the first cycle. Learn from nearby successful farmers and extension; keep records from day one.',
            ],
            [
                'id' => 'biosecurity',
                'keywords' => ['biosecurity', 'quarantine', 'disinfect', 'hygiene', 'footbath'],
                'title' => 'Biosecurity',
                'body' => 'Limit visitors, use footbaths, separate ages/batches, quarantine new animals 2+ weeks, clean/disinfect houses between cycles, and never share uncleaned nets or crates across farms. Biosecurity is the cheapest disease control.',
            ],
            [
                'id' => 'extension-safety',
                'keywords' => ['chemical', 'spray', 'ppe', 'poison', 'safety', 'label', 'nafdac'],
                'title' => 'Safe agrochemical use',
                'body' => 'Read the label: crop, rate, PPE, and pre-harvest interval. Do not mix mystery cocktails. Store chemicals locked away from children and feed. Buy from reputable agro-dealers; prefer NAFDAC-approved products. When unsure, ask an extension agent or vet before spraying or dosing.',
            ],
        ];
    }
}
