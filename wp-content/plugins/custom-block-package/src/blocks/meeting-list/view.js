document.addEventListener("DOMContentLoaded", function () {
    const flipCards = document.querySelectorAll(".flipping-cards__card");

    function setFocusable(container, enabled) {
        container.querySelectorAll("a, button, input, select, textarea, [tabindex]").forEach(function (el) {
            el.setAttribute("tabindex", enabled ? "0" : "-1");
        });
    }

    function toggleCard(card) {
        const innerCard = card.querySelector(".flipping-cards__card-inner");
        const front = card.querySelector(".flipping-cards__card-front");
        const back = card.querySelector(".flipping-cards__card-back");

        const isFlipped = innerCard.classList.toggle(
            "flipping-cards__card-inner--flipped"
        );

        card.setAttribute("aria-expanded", isFlipped ? "true" : "false");

        // Hide the non-visible side from assistive technologies.
        if (front) {
            front.setAttribute("aria-hidden", isFlipped ? "true" : "false");
            setFocusable(front, !isFlipped);
        }
        if (back) {
            back.setAttribute("aria-hidden", isFlipped ? "false" : "true");
            setFocusable(back, isFlipped);
        }
    }

    flipCards.forEach((card) => {
        // Disable focus on hidden back-side links at init.
        const back = card.querySelector(".flipping-cards__card-back");
        if (back) setFocusable(back, false);

        // Click support
        card.addEventListener("click", () => toggleCard(card));

        // Keyboard support (Enter and Space)
        card.addEventListener("keydown", (e) => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                toggleCard(card);
            }
        });
    });

    // Equalize card heights
    function equalizeCardHeights() {
        const cards = document.querySelectorAll(".flipping-cards__card");
        if (cards.length === 0) return;

        // Reset heights before measuring
        cards.forEach((card) => (card.style.height = "auto"));

        let maxHeight = 0;
        cards.forEach((card) => {
            if (card.offsetHeight > maxHeight) {
                maxHeight = card.offsetHeight;
            }
        });

        cards.forEach((card) => (card.style.height = `${maxHeight}px`));
    }

    equalizeCardHeights();
    window.addEventListener("resize", equalizeCardHeights);
});