<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, reactive } from "vue";
import AppShell from "@/Layouts/AppShell.vue";
import logo from "@/assets/images/logo_md_tours.png";
import { formatDate } from "@/utils/dateFormat";

defineOptions({ layout: AppShell });

const props = defineProps({
    planning: { type: Object, required: true },
    roadSheet: { type: Object, required: true },
});

const cleanDate = (value) => (value ? String(value).split("T")[0] : "");
const cleanTime = (value) => (value ? String(value).slice(0, 5) : "");
const displayDate = (value) => formatDate(value);

const blankLine = () => ({
    date: "",
    departure_kms: "",
    arrival_kms: "",
    distance: "",
    gasoline: "",
    jawaz: "",
    other_expenses: "",
    notes: "",
});

const initialLines = () => {
    const lines = (props.roadSheet.lines || []).map((line) => ({
        date: cleanDate(line.date),
        departure_kms: line.departure_kms ?? "",
        arrival_kms: line.arrival_kms ?? "",
        distance: line.distance ?? "",
        gasoline: line.gasoline ?? "",
        jawaz: line.jawaz ?? "",
        other_expenses: line.other_expenses ?? "",
        notes: line.notes || "",
    }));

    lines.sort((a, b) => {
        if (!a.date) return 1;
        if (!b.date) return -1;
        return a.date.localeCompare(b.date);
    });

    return lines.length ? lines : [blankLine()];
};

const form = reactive({
    pre_service_km: props.roadSheet.pre_service_km ?? 0,
    pre_service_odometer_start: props.roadSheet.pre_service_odometer_start ?? "",
    pre_service_odometer_end: props.roadSheet.pre_service_odometer_end ?? "",
    pre_service_origin: props.roadSheet.pre_service_origin || "",
    pre_service_note: props.roadSheet.pre_service_note || "",
    voucher_number:
        props.roadSheet.voucher_number || props.planning.ref_dossier || "",
    start_city:
        props.roadSheet.start_city || props.planning.point_depart || "",
    end_city:
        props.roadSheet.end_city ||
        props.planning.destination?.city ||
        props.planning.destination?.name ||
        "",
    start_flight: props.roadSheet.start_flight || props.planning.flight || "",
    end_flight: props.roadSheet.end_flight || props.planning.flight || "",
    start_time: cleanTime(props.roadSheet.start_time || props.planning.heure),
    end_time: cleanTime(props.roadSheet.end_time || props.planning.heure),
    signature_date: cleanDate(props.roadSheet.signature_date),
    signature_name: props.roadSheet.signature_name || "",
    notes: props.roadSheet.notes || "",
    lines: initialLines(),
});

const saving = reactive({ value: false });

const clientsText = computed(() => {
    const clients = props.planning.planning_clients || [];

    return (
        clients
            .map((item) => item.client?.full_name)
            .filter(Boolean)
            .join(", ") || "-"
    );
});

const totalDistance = computed(() =>
    form.lines.reduce((sum, line) => sum + Number(line.distance || 0), 0),
);
const preServiceDistance = computed(() =>
    form.pre_service_odometer_start !== "" && form.pre_service_odometer_end !== ""
        ? Math.max(0, Number(form.pre_service_odometer_end) - Number(form.pre_service_odometer_start))
        : Number(form.pre_service_km || 0),
);
const totalRealDistance = computed(() => preServiceDistance.value + totalDistance.value);

const totalGasoline = computed(() =>
    form.lines.reduce((sum, line) => sum + Number(line.gasoline || 0), 0),
);

const totalJawaz = computed(() =>
    form.lines.reduce((sum, line) => sum + Number(line.jawaz || 0), 0),
);

const totalOther = computed(() =>
    form.lines.reduce((sum, line) => sum + Number(line.other_expenses || 0), 0),
);

const totalExpenses = computed(
    () => totalGasoline.value + totalJawaz.value + totalOther.value,
);

const formatMoney = (value) =>
    Number(value || 0).toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const updateDistance = (line) => {
    const departure = Number(line.departure_kms || 0);
    const arrival = Number(line.arrival_kms || 0);

    if (departure > 0 && arrival >= departure) {
        line.distance = arrival - departure;
    }
};

const addLine = () => {
    form.lines.push(blankLine());
};

const removeLine = (index) => {
    if (form.lines.length <= 1) {
        form.lines[index] = blankLine();
        return;
    }

    form.lines.splice(index, 1);
};

const saveRoadSheet = () => {
    saving.value = true;

    router.put(route("road-sheets.update", props.roadSheet.id), form, {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Road Sheet ${planning.ref_dossier || ''}`" />

    <div class="road-sheet-page">
        <div class="container-fluid py-4">
            <div class="page-hero">
                <div class="hero-left">
                    <div class="hero-icon">
                        <i class="bx bx-trip"></i>
                    </div>
                    <div>
                        <p>MD TOURS</p>
                        <h1>Road Sheet</h1>
                        <span>
                            {{ planning.ref_dossier || "No reference" }}
                        </span>
                    </div>
                </div>

                <div class="top-actions">
                    <Link :href="route('road-sheets.index')" class="soft-btn">
                        <i class="bx bx-arrow-back me-2"></i>
                        Road Sheets
                    </Link>
                    <Link :href="route('plannings.index')" class="soft-btn">
                        <i class="bx bx-calendar me-2"></i>
                        Plannings
                    </Link>
                    <button
                        type="button"
                        class="save-btn"
                        :disabled="saving.value"
                        @click="saveRoadSheet"
                    >
                        <i class="bx bx-save me-2"></i>
                        {{ saving.value ? "Saving..." : "Save Road Sheet" }}
                    </button>
                </div>
            </div>

            <div class="sheet-card">
                <!-- <div class="sheet-header">
                    <div>
                        <img :src="logo" alt="MD Tours" />
                    </div>
                    <div class="sheet-title">
                        <span>Operational Document</span>
                        <h2>Planning Information</h2>
                    </div>
                </div> -->

                <div class="summary-grid">
                    <div class="field wide">
                        <label>Client</label>
                        <div class="readonly">{{ clientsText }}</div>
                    </div>
                    <div class="field">
                        <label>Voucher Number</label>
                        <input v-model="form.voucher_number" type="text" />
                    </div>
                    <div class="field">
                        <label>Start Date</label>
                        <div class="readonly">
                            {{ displayDate(planning.date_du) }}
                        </div>
                    </div>
                    <div class="field">
                        <label>End Date</label>
                        <div class="readonly">
                            {{ displayDate(planning.date_au) }}
                        </div>
                    </div>
                    <div class="field wide">
                        <label>Service Type</label>
                        <div class="readonly">
                            {{ planning.service?.designation || "-" }}
                        </div>
                    </div>
                    <div class="field">
                        <label>Start Point</label>
                        <div class="readonly">
                            {{ planning.point_depart || "-" }}
                        </div>
                    </div>
                    <div class="field">
                        <label>Start City</label>
                        <input v-model="form.start_city" type="text" />
                    </div>
                    <div class="field">
                        <label>Start Flight</label>
                        <input v-model="form.start_flight" type="text" />
                    </div>
                    <div class="field">
                        <label>Start Time</label>
                        <input v-model="form.start_time" type="time" />
                    </div>
                    <div class="field">
                        <label>End Point</label>
                        <div class="readonly">
                            {{ planning.destination?.name || "-" }}
                        </div>
                    </div>
                    <div class="field">
                        <label>End City</label>
                        <input v-model="form.end_city" type="text" />
                    </div>
                    <div class="field">
                        <label>End Flight</label>
                        <input v-model="form.end_flight" type="text" />
                    </div>
                    <div class="field">
                        <label>End Time</label>
                        <input v-model="form.end_time" type="time" />
                    </div>
                    <div class="field">
                        <label>MD Driver</label>
                        <div class="readonly">{{ planning.driver?.name || "-" }}</div>
                    </div>
                    <div class="field">
                        <label>Tour Guide</label>
                        <div class="readonly">{{ planning.guide?.name || "-" }}</div>
                    </div>
                    <div class="field">
                        <label>MD Tours Vehicle</label>
                        <div class="readonly">
                            {{
                                planning.vehicule?.matricule ||
                                planning.supplier_vehicule?.name ||
                                "-"
                            }}
                        </div>
                    </div>
                    <div class="field">
                        <label>Number of Passengers</label>
                        <div class="readonly">
                            {{ planning.nbr_personnes || "-" }}
                        </div>
                    </div>
                    <div class="field wide">
                        <label>Reference</label>
                        <div class="readonly">
                            {{ planning.ref_dossier || "-" }}
                        </div>
                    </div>
                </div>

                <div class="table-note">
                    <div class="note-icon"><i class="bx bx-navigation"></i></div>
                    <div style="width: 100%">
                        <strong>Déplacement avant service</strong>
                        <p>La distance avant service est calculée par différence entre les deux compteurs.</p>
                        <div class="summary-grid" style="margin-top: 14px">
                            <div class="field"><label>Localisation initiale</label><input v-model="form.pre_service_origin" type="text" maxlength="255" /></div>
                            <div class="field"><label>Compteur départ (km)</label><input v-model="form.pre_service_odometer_start" type="number" min="0" max="4294967295" step="1" /></div>
                            <div class="field"><label>Compteur arrivée (km)</label><input v-model="form.pre_service_odometer_end" type="number" min="0" max="4294967295" step="1" /></div>
                            <div class="field"><label>Distance avant service</label><div class="readonly">{{ preServiceDistance }} km</div></div>
                            <div class="field wide"><label>Note explicative</label><input v-model="form.pre_service_note" type="text" maxlength="500" /></div>
                        </div>
                    </div>
                </div>

                <div class="totals-grid">
                    <div>
                        <span>Distance circuit</span>
                        <strong>{{ totalDistance }} km</strong>
                    </div>
                    <div><span>Avant service</span><strong>{{ preServiceDistance }} km</strong></div>
                    <div><span>Distance réelle</span><strong>{{ totalRealDistance }} km</strong></div>
                    <div>
                        <span>Gasoline</span>
                        <strong>{{ formatMoney(totalGasoline) }} MAD</strong>
                    </div>
                    <div>
                        <span>Jawaz</span>
                        <strong>{{ formatMoney(totalJawaz) }} MAD</strong>
                    </div>
                    <div>
                        <span>Total Expenses</span>
                        <strong>{{ formatMoney(totalExpenses) }} MAD</strong>
                    </div>
                </div>

                <div class="table-note">
                    <div class="note-icon">
                        <i class="bx bx-info-circle"></i>
                    </div>
                    <div>
                        <strong>What is this table used for?</strong>
                        <p>
                            This table is used to detail the trip linked to this planning:
                            departure and arrival kilometers, actual distance, gasoline,
                            Jawaz, and other expenses. These lines help with cost tracking,
                            mileage control, and the preparation of reports and invoices.
                        </p>
                    </div>
                </div>

                <div class="table-section-head">
                    <div>
                        <h3>Trip and Expense Details</h3>
                        <p>Add one line per day, stop, or expense.</p>
                    </div>
                    <button type="button" class="add-line-btn" @click="addLine">
                        <i class="bx bx-plus me-2"></i>
                        Add Line
                    </button>
                </div>

                <div class="line-table-wrap">
                    <table class="line-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Departure Kms</th>
                                <th>Arrival Kms</th>
                                <th>Gasoline</th>
                                <th>Distance</th>
                                <th>Jawaz</th>
                                <th>Other Expenses</th>
                                <th>Notes</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(line, index) in form.lines" :key="index">
                                <td>
                                    <input v-model="line.date" type="date" />
                                </td>
                                <td>
                                    <input
                                        v-model="line.departure_kms"
                                        type="number"
                                        min="0"
                                        @input="updateDistance(line)"
                                    />
                                </td>
                                <td>
                                    <input
                                        v-model="line.arrival_kms"
                                        type="number"
                                        min="0"
                                        @input="updateDistance(line)"
                                    />
                                </td>
                                <td>
                                    <input
                                        v-model="line.gasoline"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                    />
                                </td>
                                <td>
                                    <input
                                        v-model="line.distance"
                                        type="number"
                                        min="0"
                                    />
                                </td>
                                <td>
                                    <input
                                        v-model="line.jawaz"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                    />
                                </td>
                                <td>
                                    <input
                                        v-model="line.other_expenses"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                    />
                                </td>
                                <td>
                                    <input v-model="line.notes" type="text" />
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="remove-line"
                                        @click="removeLine(index)"
                                    >
                                        <i class="bx bx-x"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="footer-grid">
                    <div class="signature-card">
                        <h3>Approval</h3>
                        <div class="signature-row">
                            <div class="field">
                                <label>Date</label>
                                <input
                                    v-model="form.signature_date"
                                    type="date"
                                />
                            </div>
                            <div class="field signature-field">
                                <label>Signature</label>
                                <input
                                    v-model="form.signature_name"
                                    type="text"
                                    placeholder="Name / Signature"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="field notes-field">
                        <label>Internal Notes</label>
                        <textarea
                            v-model="form.notes"
                            rows="5"
                            placeholder="Comments, driver notes, incidents..."
                        ></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.road-sheet-page {
    min-height: 100vh;
    background:
        radial-gradient(circle at top left, rgba(193, 18, 31, 0.06), transparent 28%),
        #f5f6fa;
}

.page-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    max-width: 1320px;
    margin: 0 auto 20px;
    padding: 24px 28px;
    border-radius: 24px;
    background: linear-gradient(135deg, #78000b, #172554);
    box-shadow: 0 20px 46px rgba(15, 23, 42, 0.18);
}

.hero-left,
.top-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.hero-icon {
    width: 58px;
    height: 58px;
    display: grid;
    place-items: center;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.13);
    color: #fff;
    font-size: 30px;
}

.hero-left p {
    margin: 0 0 5px;
    color: rgba(255, 255, 255, 0.72);
    font-weight: 900;
}

.hero-left h1 {
    margin: 0;
    color: #fff;
    font-weight: 950;
}

.hero-left span {
    display: inline-flex;
    margin-top: 7px;
    color: #fff;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 999px;
    padding: 6px 12px;
    font-weight: 800;
}

.soft-btn,
.save-btn,
.add-line-btn {
    border: 0;
    border-radius: 14px;
    padding: 12px 16px;
    font-weight: 900;
    text-decoration: none;
    white-space: nowrap;
    transition:
        transform 0.16s ease,
        box-shadow 0.16s ease,
        background 0.16s ease;
}

.soft-btn {
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.18);
}

.save-btn,
.add-line-btn {
    background: #c1121f;
    color: #fff;
    box-shadow: 0 12px 24px rgba(193, 18, 31, 0.22);
}

.soft-btn:hover,
.save-btn:hover,
.add-line-btn:hover {
    color: #fff;
    transform: translateY(-1px);
}

.sheet-card {
    max-width: 1320px;
    margin: 0 auto;
    background: #fff;
    border: 1px solid #e8edf5;
    border-radius: 24px;
    padding: 28px;
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.09);
}

.sheet-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    margin-bottom: 22px;
    padding-bottom: 20px;
    border-bottom: 1px solid #edf0f5;
}

.sheet-header img {
    width: 190px;
    max-width: 100%;
}

.sheet-title {
    text-align: right;
}

.sheet-title span {
    color: #64748b;
    font-weight: 900;
}

.sheet-title h2 {
    margin: 4px 0 0;
    color: #111827;
    font-weight: 950;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px 18px;
}

.field {
    min-width: 0;
}

.field.wide {
    grid-column: span 2;
}

.field label {
    display: block;
    margin-bottom: 6px;
    color: #475569;
    font-weight: 900;
    font-size: 13px;
}

.field input,
.field textarea,
.readonly,
.line-table input {
    width: 100%;
    border: 1px solid #d8e0ec;
    border-radius: 10px;
    padding: 10px 11px;
    color: #111827;
    background: #fff;
    font-weight: 700;
}

.readonly {
    min-height: 40px;
    background: #f8fafc;
    color: #172033;
    font-weight: 800;
}

.totals-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin: 26px 0;
}

.totals-grid div {
    border-radius: 18px;
    padding: 18px;
    background: linear-gradient(180deg, #fbfcff, #f8fafc);
    border: 1px solid #e8edf5;
}

.totals-grid span {
    display: block;
    color: #64748b;
    font-weight: 800;
    margin-bottom: 4px;
}

.totals-grid strong {
    color: #7a0610;
    font-size: 22px;
    font-weight: 950;
}

.table-note {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    margin: 8px 0 18px;
    padding: 16px 18px;
    border: 1px solid #bfdbfe;
    border-radius: 18px;
    background: #eff6ff;
    color: #1e3a8a;
}

.note-icon {
    width: 38px;
    height: 38px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    border-radius: 12px;
    background: #dbeafe;
    color: #1d4ed8;
    font-size: 22px;
}

.table-note strong {
    display: block;
    margin-bottom: 4px;
    font-weight: 950;
}

.table-note p {
    margin: 0;
    color: #1e40af;
    line-height: 1.55;
    font-weight: 650;
}

.table-section-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin: 22px 0 14px;
}

.table-section-head h3,
.signature-card h3 {
    margin: 0;
    color: #111827;
    font-weight: 950;
}

.table-section-head p {
    margin: 5px 0 0;
    color: #64748b;
    font-weight: 700;
}

.line-table-wrap {
    overflow-x: auto;
    border: 1px solid #d8e0ec;
    border-radius: 16px;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.line-table {
    width: 100%;
    min-width: 1040px;
    border-collapse: collapse;
    table-layout: fixed;
}

.line-table th {
    background: #f8fafc;
    padding: 13px 12px;
    color: #7a0610;
    font-size: 13px;
    font-weight: 950;
    letter-spacing: 0;
    white-space: nowrap;
}

.line-table th:nth-child(1) {
    width: 155px;
}

.line-table th:nth-child(2),
.line-table th:nth-child(3),
.line-table th:nth-child(5) {
    width: 140px;
}

.line-table th:nth-child(4),
.line-table th:nth-child(6),
.line-table th:nth-child(7) {
    width: 135px;
}

.line-table th:nth-child(9) {
    width: 58px;
}

.line-table td {
    border-top: 1px solid #edf0f5;
    border-right: 1px solid #edf0f5;
    padding: 8px;
    background: #fff;
}

.line-table tbody tr:nth-child(even) td {
    background: #fbfcfe;
}

.line-table input {
    border-color: transparent;
    background: transparent;
}

.line-table input:focus {
    border-color: #93c5fd;
    background: #fff;
    outline: 0;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

.remove-line {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 12px;
    background: #fee2e2;
    color: #b91c1c;
    font-weight: 900;
}

.add-line-btn {
    margin-top: 0;
}

.footer-grid {
    display: grid;
    grid-template-columns: minmax(360px, 1fr) minmax(320px, 1fr);
    align-items: start;
    gap: 18px;
    margin-top: 28px;
}

.signature-card,
.notes-field {
    border: 1px solid #e8edf5;
    border-radius: 18px;
    padding: 18px;
    background: #fbfcff;
}

.signature-row {
    display: grid;
    grid-template-columns: minmax(160px, 220px) minmax(220px, 1fr);
    gap: 18px;
    margin-top: 14px;
}

.signature-field input {
    min-height: 74px;
}

.notes-field {
    margin-top: 0;
}

@media (max-width: 992px) {
    .summary-grid,
    .totals-grid,
    .footer-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .page-hero,
    .sheet-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .sheet-title {
        text-align: left;
    }
}

@media (max-width: 640px) {
    .top-actions,
    .signature-row {
        flex-direction: column;
        display: flex;
        align-items: stretch;
    }

    .sheet-card {
        padding: 20px;
    }

    .summary-grid,
    .totals-grid,
    .footer-grid {
        grid-template-columns: 1fr;
    }

    .field.wide {
        grid-column: span 1;
    }
}
</style>
