"""
Generates a synthetic, barangay-representative dataset for training the
request-type and urgency classifiers.

This is clearly a RESEARCHER-CONSTRUCTED dataset (per the proposal's Section
6.2 disclosure on data limitations) meant to bootstrap the models until real,
anonymized barangay incident logs are available. Text mixes Filipino/Bikol
code-switching patterns typical of real resident reports.
"""
import csv
import itertools
import random

random.seed(42)

# Each tuple: (category, urgency, [template sentences])
TEMPLATES = [
    ("medical", "critical", [
        "tulong po, hindi na humihinga ang lolo ko",
        "unconscious po siya at hindi gumagalaw, kailangan ng ambulansya agad",
        "severe bleeding po sa sugat, kailangan agad ng tulong medikal",
        "chest pain at hirap huminga ang asawa ko, nanganganib mabuhay",
        "nalunod po ang bata sa ilog, hindi humihinga",
        "nagkaroon ng seizure at hindi na natitigan ang mata, kailangan ng ambulansya ngayon din",
        "sobrang laki ng dugo na lumalabas sa sugat niya, mamamatay na po siya",
    ]),
    ("medical", "high", [
        "may lagnat na mataas ang anak ko, sumusuka na rin",
        "sumasakit ng husto ang tiyan ng kapitbahay namin",
        "nadapa ang matanda, parang nabali ang binti niya",
        "hirap huminga ang lola ko pero kumakausap pa naman",
        "may sugat na malalim sa binti, dumudugo pa rin",
        "nahihilo at nanghihina ang buntis, kailangan dalhin sa ospital",
    ]),
    ("medical", "average", [
        "pwede po ba mahingi ng gamot para sa ubo",
        "need po sana ng first aid kit dito sa amin",
        "may sugat lang po pero hindi na dumudugo, pakitingnan na lang",
        "sumasakit ang ulo niya mula kagabi, di naman sobra",
    ]),
    ("medical", "low", [
        "tanong ko lang po kung may libreng bakuna ngayong linggo",
        "kailan po ang schedule ng check-up sa barangay health center",
        "meron po bang dental mission dito sa amin",
        "paano po mag-avail ng senior citizen discount sa gamot",
    ]),

    ("fire", "critical", [
        "sunog po sa bahay namin, malaki na ang apoy",
        "may nasusunog na bahay malapit sa amin, tumulong kayo agad",
        "kumakalat na ang apoy sa mga kalapit bahay, kailangan ng bumbero ngayon",
        "sumasabog ang gasul sa kusina at may apoy na, tulungan niyo kami",
    ]),
    ("fire", "high", [
        "may usok na lumalabas sa kalapit bahay, baka may sunog",
        "nag-iinit ng husto ang de-koryenteng gamit at may umuusok",
        "may nakikitang liyab sa likod ng bahay ng kapitbahay",
    ]),
    ("fire", "average", [
        "amoy paso po sa kanto, pakitingnan naman",
        "may naaamoy na sunog pero di na makita kung saan galing",
    ]),
    ("fire", "low", [
        "tanong lang po kung meron kayong fire safety seminar",
        "paano po humingi ng fire extinguisher para sa aming sari-sari store",
    ]),

    ("peace_order", "critical", [
        "may taong may dalang baril at nananakot sa amin ngayon",
        "may nag-aaway gamit ang kutsilyo sa kalye, may nasaktan na",
        "may holdaper na may hawak na patalim sa may tindahan",
        "sinasaktan ng lalaki ang asawa niya ngayon, sumisigaw siya ng tulong",
    ]),
    ("peace_order", "high", [
        "may nag-iingay at lasing na tao sa labas ng bahay namin, nakakatakot na",
        "may nakita kaming kahina-hinalang tao na umaakyat sa bakod ng kapitbahay",
        "may sumisigaw na away sa kabilang kalye, tumataas na boses",
    ]),
    ("peace_order", "average", [
        "may nagnanakaw daw sa aming kalye, pakisuyo tingnan",
        "may motor na naiwan sa kalye ng ilang araw na, baka nakaw",
    ]),
    ("peace_order", "low", [
        "pwede po ba magpatulong sa usapin ng hangganan ng lupa namin",
        "paano po mag-file ng blotter report sa minor na sagutan",
    ]),

    ("disaster", "critical", [
        "bumabaha na po nang husto dito, tumataas na ang tubig sa bahay namin",
        "gumuho po ang bahay dahil sa lindol, may nakulong sa loob",
        "tinangay ng baha ang bata, kailangan agad ng rescue",
        "malakas na landslide sa likod ng bahay namin, natatakot kaming lumabas",
    ]),
    ("disaster", "high", [
        "malakas ang hangin at ulan, natatakot kami baka bumagsak ang puno",
        "tumataas nang mabilis ang tubig sa creek malapit sa amin",
        "may bumagsak na malaking puno malapit sa mga bahay",
    ]),
    ("disaster", "average", [
        "may sirang poste ng kuryente dahil sa bagyo kahapon",
        "may baradong kanal na sanhi ng pagbaha sa amin tuwing umuulan",
    ]),
    ("disaster", "low", [
        "tanong ko lang kung may typhoon signal ba ngayon dito",
        "meron po bang evacuation drill sa barangay this month",
    ]),

    ("general_assistance", "high", [
        "kailangan po namin ng tulong dahil naiwan kami ng byahe at wala nang pambili ng gamot",
        "walang matulugan ang pamilya namin ngayong gabi dahil sa sunog kahapon",
    ]),
    ("general_assistance", "average", [
        "pwede po ba humingi ng tulong sa barangay para sa burial assistance",
        "meron po bang tulong pang-relief goods available ngayon",
        "kailangan namin ng tulong pang-ayuda dahil nawalan ng trabaho ang tatay ko",
    ]),
    ("general_assistance", "low", [
        "paano po mag-apply ng barangay clearance",
        "ano po ang requirements para sa indigency certificate",
        "saan po pwede kumuha ng business permit dito sa barangay",
        "anong oras po nagbubukas ang barangay hall tuwing sabado",
    ]),
]

PREFIXES = ["", "Good day po. ", "Tulong po. ", "Bar. ", "Mano po. ", "Hi po, "]
SUFFIXES = ["", " Salamat po.", " Pakitingnan po agad.", " Sana matulungan niyo kami."]


def build_rows(n_variants=6):
    rows = []
    for category, urgency, templates in TEMPLATES:
        for t_idx, template in enumerate(templates):
            base_template = f"{category}_{urgency}_{t_idx}"
            combos = list(itertools.product(PREFIXES, SUFFIXES))
            random.shuffle(combos)
            for prefix, suffix in combos[:n_variants]:
                text = f"{prefix}{template}{suffix}".strip()
                rows.append((text, category, urgency, base_template))
    random.shuffle(rows)
    return rows


if __name__ == "__main__":
    rows = build_rows()
    with open("/home/claude/barangay-sagip/ml-service/data/sample_requests.csv", "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["text", "category", "urgency", "base_template"])
        writer.writerows(rows)
    print(f"Generated {len(rows)} rows across {len(set(r[3] for r in rows))} base templates")
