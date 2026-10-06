const invoiceEscape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[c]));
let lineIndex = 0;

function calcUnitPrice(species, thickness, width, length, tierSurcharge) {
    let base = 0;
    if (species.pricing_type === "flat") {
        base = species.flat_price;
    } else {
        const prices = window.WIDTH_PRICES.filter(
            (w) => w.lumber_species_id === species.id,
        );
        const exact = prices.find(
            (w) => parseFloat(w.width) === parseFloat(width),
        );
        if (exact) {
            base = exact.base_price;
        } else if (prices.length > 0) {
            let closest = prices[0];
            let minDiff = Math.abs(
                parseFloat(closest.width) - parseFloat(width),
            );
            prices.forEach((w) => {
                const diff = Math.abs(parseFloat(w.width) - parseFloat(width));
                if (diff < minDiff) {
                    closest = w;
                    minDiff = diff;
                }
            });
            base = closest.base_price;
        }
    }

    let thicknessSurcharge = 0;
    if (species.apply_thickness_surcharge) {
        const applicable = window.THICKNESS_SURCHARGES.filter(
            (t) => t.min_thickness <= parseFloat(thickness),
        ).sort((a, b) => b.min_thickness - a.min_thickness);
        thicknessSurcharge =
            applicable.length > 0 ? applicable[0].surcharge_amount : 0;
    }

    let lengthAdjustment = 0;
    if (species.apply_length_adjustment) {
        const baseLength = parseFloat(
            window.PRICING_SETTINGS.base_length_m ?? 4,
        );
        const perMeter = parseFloat(
            window.PRICING_SETTINGS.price_per_meter_diff ?? 10,
        );
        const minimum = parseFloat(window.PRICING_SETTINGS.base_price_min_length_m ?? Math.min(3.7, baseLength));
        const actualLength = parseFloat(length);
        lengthAdjustment = actualLength >= minimum && actualLength <= baseLength
            ? 0 : (actualLength - baseLength) * perMeter;
    }

    return Math.round(
        base + thicknessSurcharge + lengthAdjustment + tierSurcharge,
    );
}

function createLineElement(data = null) {
    const idx = lineIndex++;
    const wrapper = document.createElement("div");
    wrapper.className = "border rounded p-4 line-row";
    wrapper.dataset.index = idx;

    const speciesOptions = window.SPECIES.map(
        (s) =>
            `<option value="${s.id}" ${data && String(data.lumber_species_id) === String(s.id) ? "selected" : ""}>${invoiceEscape(s.name)}</option>`,
    ).join("");

    const tierInputs = window.TIERS.map((t) => {
        const qty =
            data && data.quantities ? (data.quantities[t.label] ?? 0) : 0;
        return `
        <div class="text-center">
            <div class="text-xs text-gray-500 mb-1">${invoiceEscape(t.label)}枚</div>
            <input type="number" min="0" class="tier-qty w-full border rounded p-1 text-center"
                   data-tier-id="${t.id}" data-tier-label="${invoiceEscape(t.label)}" data-surcharge="${t.surcharge_amount}"
                   name="lines[${idx}][quantities][${t.id}]" value="${invoiceEscape(qty)}">
            <div class="text-xs tier-unit-price text-gray-400 mt-1">単価: -</div>
            <div class="text-xs tier-amount text-gray-600">¥0</div>
        </div>`;
    }).join("");

    wrapper.innerHTML = `
        <div class="grid grid-cols-6 gap-2 mb-3">
            <div class="col-span-2">
                <label class="block text-xs text-gray-500">樹種</label>
                <select name="lines[${idx}][lumber_species_id]" class="line-species w-full border rounded p-1">
                    ${speciesOptions}
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500">厚み(mm)</label>
                <input type="number" step="1" name="lines[${idx}][thickness]" class="line-thickness w-full border rounded p-1" value="${invoiceEscape(data ? data.thickness : "")}">
            </div>
            <div>
                <label class="block text-xs text-gray-500">幅(cm)</label>
                <input type="number" step="0.5" name="lines[${idx}][width]" class="line-width w-full border rounded p-1" value="${invoiceEscape(data ? data.width : "")}">
            </div>
            <div>
                <label class="block text-xs text-gray-500">長さ(m)</label>
                <input type="number" step="0.1" name="lines[${idx}][length]" class="line-length w-full border rounded p-1" value="${invoiceEscape(data ? data.length : 4)}">
            </div>
            <div style="display: ${Number(data?.adjustment_amount || 0) !== 0 ? 'block' : 'none'};">
                <label class="block text-xs text-gray-500">既存の明細調整（円）</label>
                <input type="number" name="lines[${idx}][adjustment_amount]" class="line-adjustment w-full border rounded p-1" value="${invoiceEscape(data ? data.adjustment_amount : 0)}">
            </div>
        </div>
        <div class="mb-3">
            <label class="block text-sm">単価調整（円／枚・5円刻み）
                <input type="number" step="5" name="lines[${idx}][unit_price_adjustment]" class="line-unit-adjustment border rounded p-1 w-28" value="${Number(data?.unit_price_adjustment || 0)}">
            </label>
            <p class="text-xs text-gray-500">元単価に加減します。値引きは -5、-10、加算は 5、10 など。</p>
        </div>
        <div class="grid grid-cols-${window.TIERS.length + 2} gap-2 items-end">
            ${tierInputs}
            <div class="text-center">
                <label class="block text-xs text-gray-500 mb-1">戻し
                    <input type="number" min="0" max="1000000" step="1" name="lines[${idx}][returned_quantity]" class="line-returned w-full border rounded p-1 text-center" value="${invoiceEscape(data?.returned_quantity ?? 0)}">
                </label>
                <div class="text-xs text-gray-500">金額対象外</div>
            </div>
            <div class="text-right">
                <div class="text-xs text-gray-500">合計枚数</div>
                <div class="line-qty-total font-bold">0枚</div>
                <div class="text-xs text-gray-500 mt-1">行合計</div>
                <div class="line-total font-bold">¥0</div>
                <button type="button" class="remove-line text-red-500 text-xs mt-1">削除</button>
            </div>
        </div>
    `;

    wrapper
        .querySelectorAll(
            ".tier-qty, .line-species, .line-thickness, .line-width, .line-length, .line-adjustment, .line-unit-adjustment, .line-returned",
        )
        .forEach((el) =>
            el.addEventListener("input", () => recalcLine(wrapper)),
        );

    wrapper.querySelector(".remove-line").addEventListener("click", () => {
        wrapper.remove();
        recalcGrandTotal();
    });

    return wrapper;
}

function recalcLine(wrapper) {
    const speciesId = parseInt(wrapper.querySelector(".line-species").value);
    const species = window.SPECIES.find((s) => s.id === speciesId);
    const thickness = wrapper.querySelector(".line-thickness").value || 0;
    const width = wrapper.querySelector(".line-width").value || 0;
    const length = wrapper.querySelector(".line-length").value || 0;
    const adjustment = parseInt(
        wrapper.querySelector(".line-adjustment").value || 0,
    );

    const unitAdjustment = Number(wrapper.querySelector(".line-unit-adjustment").value || 0);
    let lineTotal = adjustment;
    let qtyTotal = Number(wrapper.querySelector(".line-returned").value || 0);

    wrapper.querySelectorAll(".tier-qty").forEach((input) => {
        const qty = parseInt(input.value || 0);
        const surcharge = parseInt(input.dataset.surcharge);
        const baseUnitPrice = species
            ? calcUnitPrice(species, thickness, width, length, surcharge)
            : 0;
        const unitPrice = baseUnitPrice + unitAdjustment;
        input.setCustomValidity(qty > 0 && unitPrice < 0 ? "調整後の単価は0円以上にしてください。" : "");
        const amount = qty * unitPrice;

        const cell = input.closest("div");
        cell.querySelector(".tier-unit-price").textContent =
            `元単価 ¥${baseUnitPrice.toLocaleString()} → ¥${unitPrice.toLocaleString()}`;
        cell.querySelector(".tier-amount").textContent =
            `¥${amount.toLocaleString()}`;

        lineTotal += amount;
        qtyTotal += qty;
    });

    wrapper.querySelector(".line-qty-total").textContent =
        `${qtyTotal.toLocaleString()}枚`;
    wrapper.querySelector(".line-total").textContent =
        `¥${lineTotal.toLocaleString()}`;
    recalcGrandTotal();
}

let productLineIndex = 0;

function createProductLineElement(data = null) {
    const idx = productLineIndex++;
    const wrapper = document.createElement("div");
    wrapper.className =
        "grid grid-cols-6 gap-2 items-end product-line-row border-b pb-2";
    wrapper.dataset.index = idx;

    const productOptions = ['<option value="">(任意入力)</option>']
        .concat(
            window.PRODUCTS.map(
                (p) =>
                    `<option value="${p.id}" data-price="${p.unit_price}" data-tax="${p.tax_rate}" data-name="${invoiceEscape(p.name)}"
                ${data && data.product_id === p.id ? "selected" : ""}>${invoiceEscape(p.name)}</option>`,
            ),
        )
        .join("");

    wrapper.innerHTML = `
        <div class="col-span-1">
            <label class="block text-xs text-gray-500">品目選択</label>
            <select class="product-select w-full border rounded p-1">
                ${productOptions}
            </select>
        </div>
        <div class="col-span-2">
            <label class="block text-xs text-gray-500">品名</label>
            <input type="text" class="product-description w-full border rounded p-1" name="product_lines[${idx}][description]" value="${invoiceEscape(data ? data.description : "")}">
        </div>
        <div>
            <label class="block text-xs text-gray-500">数量</label>
            <input type="number" step="0.01" class="product-quantity w-full border rounded p-1" name="product_lines[${idx}][quantity]" value="${invoiceEscape(data ? data.quantity : 1)}">
        </div>
        <div>
            <label class="block text-xs text-gray-500">単価</label>
            <input type="number" class="product-unit-price w-full border rounded p-1" name="product_lines[${idx}][unit_price]" value="${invoiceEscape(data ? data.unit_price : "")}">
        </div>
        <div>
            <label class="block text-xs text-gray-500">税率</label>
            <select class="product-tax-rate w-full border rounded p-1" name="product_lines[${idx}][tax_rate]">
                <option value="10" ${data && data.tax_rate == 10 ? "selected" : ""}>10%</option>
                <option value="8" ${data && data.tax_rate == 8 ? "selected" : ""}>8%</option>
            </select>
        </div>
        <input type="hidden" class="product-id" name="product_lines[${idx}][product_id]" value="${data && data.product_id ? data.product_id : ""}">
        <div class="text-right">
            <div class="product-line-amount text-sm font-bold">¥0</div>
            <button type="button" class="remove-product-line text-red-500 text-xs">削除</button>
        </div>
    `;

    const select = wrapper.querySelector(".product-select");
    select.addEventListener("change", () => {
        const opt = select.options[select.selectedIndex];
        wrapper.querySelector(".product-id").value = opt.value;
        if (opt.value) {
            wrapper.querySelector(".product-description").value =
                opt.dataset.name;
            wrapper.querySelector(".product-unit-price").value =
                opt.dataset.price;
            wrapper.querySelector(".product-tax-rate").value = opt.dataset.tax;
        }
        recalcProductLine(wrapper);
    });

    wrapper
        .querySelectorAll(
            ".product-quantity, .product-unit-price, .product-tax-rate, .product-description",
        )
        .forEach((el) =>
            el.addEventListener("input", () => recalcProductLine(wrapper)),
        );

    wrapper
        .querySelector(".remove-product-line")
        .addEventListener("click", () => {
            wrapper.remove();
            recalcGrandTotal();
        });

    return wrapper;
}

function recalcProductLine(wrapper) {
    const qty = parseFloat(
        wrapper.querySelector(".product-quantity").value || 0,
    );
    const price = parseInt(
        wrapper.querySelector(".product-unit-price").value || 0,
    );
    const amount = Math.round(qty * price);
    wrapper.querySelector(".product-line-amount").textContent =
        `¥${amount.toLocaleString()}`;
    recalcGrandTotal();
}

function recalcGrandTotal() {
    let subtotal10 = 0;
    let subtotal8 = 0;

    document.querySelectorAll(".line-row").forEach((wrapper) => {
        const text = wrapper
            .querySelector(".line-total")
            .textContent.replace(/[¥,]/g, "");
        subtotal10 += parseInt(text || 0);
    });

    document.querySelectorAll(".product-line-row").forEach((wrapper) => {
        const text = wrapper
            .querySelector(".product-line-amount")
            .textContent.replace(/[¥,]/g, "");
        const taxRate = wrapper.querySelector(".product-tax-rate").value;
        const amount = parseInt(text || 0);
        if (taxRate === "8") {
            subtotal8 += amount;
        } else {
            subtotal10 += amount;
        }
    });

    subtotal10 += Number(document.getElementById('invoice-adjustment').value || 0);
    document.getElementById('invoice-adjustment').setCustomValidity(subtotal10 < 0 ? '調整後の税抜小計は0円以上にしてください。' : '');
    const tax10 = Math.round(subtotal10 * 0.1);
    const tax8 = Math.round(subtotal8 * 0.08);
    const subtotal = subtotal10 + subtotal8;
    const tax = tax10 + tax8;

    document.getElementById("grand-subtotal").textContent =
        `¥${subtotal.toLocaleString()}`;
    document.getElementById("grand-tax").textContent =
        `¥${tax.toLocaleString()}`;
    document.getElementById("grand-total").textContent =
        `¥${(subtotal + tax).toLocaleString()}`;
}

document.getElementById("add-line").addEventListener("click", () => {
    const el = createLineElement();
    document.getElementById("lines-container").appendChild(el);
});

document.getElementById("add-product-line").addEventListener("click", () => {
    const el = createProductLineElement();
    document.getElementById("product-lines-container").appendChild(el);
});

// 既存データがあれば復元、なければ空行を1つ表示
document.addEventListener("DOMContentLoaded", () => {
    if (window.EXISTING_LINES && window.EXISTING_LINES.length > 0) {
        window.EXISTING_LINES.forEach((line) => {
            const el = createLineElement(line);
            document.getElementById("lines-container").appendChild(el);
            recalcLine(el);
        });
    } else {
        document.getElementById("add-line").click();
    }

    if (
        window.EXISTING_PRODUCT_LINES &&
        window.EXISTING_PRODUCT_LINES.length > 0
    ) {
        window.EXISTING_PRODUCT_LINES.forEach((line) => {
            const el = createProductLineElement(line);
            document.getElementById("product-lines-container").appendChild(el);
            recalcProductLine(el);
        });
    }
});

document.getElementById('invoice-adjustment').addEventListener('input', recalcGrandTotal);
