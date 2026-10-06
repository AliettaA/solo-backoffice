let lineCounter = 0;

function createLineElement(side, data = null) {
    const idx = lineCounter++;
    const wrapper = document.createElement("div");
    wrapper.className = "grid grid-cols-12 gap-1 items-end line-row";
    wrapper.dataset.side = side;

    if (side === 'debit') {
        const categoryOptions = window.CATEGORIES.map(c =>
            `<option value="${c.id}" ${data && String(data.expense_category_id) === String(c.id) ? 'selected' : ''}>${c.name}</option>`
        ).join('');

        const isOtherPayee = data && data.counterparty_name && !window.PAYEES.includes(data.counterparty_name);
        const payeeOptions = ['<option value="">(なし)</option>'].concat(
            window.PAYEES.map(name =>
                `<option value="${name}" ${data && data.counterparty_name === name ? 'selected' : ''}>${name}</option>`
            )
        ).join('') + `<option value="other" ${isOtherPayee ? 'selected' : ''}>その他</option>`;

        wrapper.innerHTML = `
            <div class="col-span-4">
                <select class="line-category w-full border rounded p-1 text-sm" name="lines[${idx}][expense_category_id]">
                    ${categoryOptions}
                </select>
            </div>
            <div class="col-span-3">
                <select class="line-payee w-full border rounded p-1 text-sm">
                    ${payeeOptions}
                </select>
                <input type="text" class="other-payee-name w-full border rounded p-1 text-sm mt-1" placeholder="取引先名を入力" style="display: ${isOtherPayee ? 'block' : 'none'};" value="${isOtherPayee ? data.counterparty_name : ''}">
                <input type="hidden" class="line-counterparty" name="lines[${idx}][counterparty_name]" value="${data ? (data.counterparty_name || '') : ''}">
            </div>
            <div class="col-span-3">
                <input type="number" class="line-amount w-full border rounded p-1 text-sm" name="lines[${idx}][amount]" placeholder="金額" value="${data ? data.amount : ''}">
            </div>
            <div class="col-span-2 text-right">
                <button type="button" class="remove-line text-red-500 text-xs">削除</button>
            </div>
            <input type="hidden" name="lines[${idx}][side]" value="${side}">
        `;

        const payeeSelect = wrapper.querySelector('.line-payee');
        const otherPayeeInput = wrapper.querySelector('.other-payee-name');
        const hiddenCounterparty = wrapper.querySelector('.line-counterparty');

        function syncCounterparty() {
            if (payeeSelect.value === 'other') {
                otherPayeeInput.style.display = 'block';
                hiddenCounterparty.value = otherPayeeInput.value;
            } else {
                otherPayeeInput.style.display = 'none';
                hiddenCounterparty.value = payeeSelect.value;
            }
        }

        payeeSelect.addEventListener('change', syncCounterparty);
        otherPayeeInput.addEventListener('input', syncCounterparty);

        wrapper.querySelector('.line-amount').addEventListener('input', recalcTotals);
        wrapper.querySelector('.remove-line').addEventListener('click', () => {
            wrapper.remove();
            recalcTotals();
        });

        return wrapper;
    }

    // 貸方(credit)側: 絞り込んだ科目 + その他の自由入力
    const isOther =
        data &&
        !window.CREDIT_CATEGORIES.some(
            (c) => String(c.id) === String(data.expense_category_id),
        );
    const categoryOptions =
        window.CREDIT_CATEGORIES.map(
            (c) =>
                `<option value="${c.id}" ${data && String(data.expense_category_id) === String(c.id) ? "selected" : ""}>${c.name}</option>`,
        ).join("") +
        `<option value="other" ${isOther ? "selected" : ""}>その他</option>`;

    wrapper.innerHTML = `
        <div class="col-span-4">
            <select class="line-category w-full border rounded p-1 text-sm" name="lines[${idx}][expense_category_id]">
                ${categoryOptions}
            </select>
        </div>
        <div class="col-span-3 other-category-wrap" style="display: ${isOther ? "block" : "none"};">
            <input type="text" class="other-category-name w-full border rounded p-1 text-sm" placeholder="科目名を入力">
        </div>
        <div class="col-span-${isOther ? "3" : "6"} amount-wrap">
            <input type="number" class="line-amount w-full border rounded p-1 text-sm" name="lines[${idx}][amount]" placeholder="金額" value="${data ? data.amount : ""}">
        </div>
        <div class="col-span-2 text-right">
            <button type="button" class="remove-line text-red-500 text-xs">削除</button>
        </div>
        <input type="hidden" name="lines[${idx}][side]" value="${side}">
    `;

    const otherName = data ? (data.new_category_name || window.CATEGORIES.find(c => String(c.id) === String(data.expense_category_id))?.name || '') : '';
    wrapper.querySelector('.other-category-name').value = otherName;
    const select = wrapper.querySelector(".line-category");
    const otherWrap = wrapper.querySelector(".other-category-wrap");
    const amountWrap = wrapper.querySelector(".amount-wrap");

    select.addEventListener("change", () => {
        if (select.value === "other") {
            otherWrap.style.display = "block";
            amountWrap.classList.remove("col-span-6");
            amountWrap.classList.add("col-span-3");
        } else {
            otherWrap.style.display = "none";
            amountWrap.classList.remove("col-span-3");
            amountWrap.classList.add("col-span-6");
        }
    });

    wrapper
        .querySelector(".line-amount")
        .addEventListener("input", recalcTotals);
    wrapper.querySelector(".remove-line").addEventListener("click", () => {
        wrapper.remove();
        recalcTotals();
    });

    return wrapper;
}

function recalcTotals() {
    let debitTotal = 0;
    let creditTotal = 0;

    document
        .querySelectorAll("#debit-container .line-amount")
        .forEach((input) => {
            debitTotal += parseInt(input.value || 0);
        });
    document
        .querySelectorAll("#credit-container .line-amount")
        .forEach((input) => {
            creditTotal += parseInt(input.value || 0);
        });

    document.getElementById("debit-total").textContent =
        `¥${debitTotal.toLocaleString()}`;
    document.getElementById("credit-total").textContent =
        `¥${creditTotal.toLocaleString()}`;

    const balanceEl = document.getElementById("balance-check");
    if (debitTotal === 0 && creditTotal === 0) {
        balanceEl.textContent = "";
        balanceEl.className = "p-3 rounded mb-4 text-center font-bold";
    } else if (debitTotal === creditTotal) {
        balanceEl.textContent = "✓ 貸借は一致しています";
        balanceEl.className =
            "p-3 rounded mb-4 text-center font-bold bg-green-100 text-green-800";
    } else {
        const diff = Math.abs(debitTotal - creditTotal);
        balanceEl.textContent = `✗ 貸借が一致していません(差額 ¥${diff.toLocaleString()})`;
        balanceEl.className =
            "p-3 rounded mb-4 text-center font-bold bg-red-100 text-red-800";
    }
}

document.getElementById("add-debit").addEventListener("click", () => {
    const el = createLineElement("debit");
    document.getElementById("debit-container").appendChild(el);
});

document.getElementById("add-credit").addEventListener("click", () => {
    const el = createLineElement("credit");
    document.getElementById("credit-container").appendChild(el);
});

document.addEventListener("DOMContentLoaded", () => {
    if (window.EXISTING_LINES && window.EXISTING_LINES.length > 0) {
        window.EXISTING_LINES.forEach((line) => {
            const el = createLineElement(line.side, line);
            const containerId =
                line.side === "debit" ? "debit-container" : "credit-container";
            document.getElementById(containerId).appendChild(el);
        });
    } else {
        document.getElementById("add-debit").click();
        document.getElementById("add-credit").click();
    }

    recalcTotals();
});

document.getElementById("entry-form").addEventListener("submit", function (e) {
    const otherSelects = document.querySelectorAll(
        "#credit-container .line-category",
    );
    let hasEmptyOther = false;

    otherSelects.forEach((select) => {
        if (select.value === "other") {
            const wrapper = select.closest(".line-row");
            const otherInput = wrapper.querySelector(".other-category-name");
            if (!otherInput.value.trim()) {
                hasEmptyOther = true;
            } else {
                // 隠しフィールドとして科目名を追加送信(サーバー側で新規科目作成に使う)
                const hidden = document.createElement("input");
                hidden.type = "hidden";
                hidden.name = select.name.replace(
                    "[expense_category_id]",
                    "[new_category_name]",
                );
                hidden.value = otherInput.value.trim();
                wrapper.appendChild(hidden);
            }
        }
    });

    if (hasEmptyOther) {
        e.preventDefault();
        alert("「その他」を選んだ行には、科目名を入力してください。");
    }
});