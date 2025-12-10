const dropdownBtn = document.getElementById("dropdownBtn");
const dropdownList = document.getElementById("dropdownList");
const arrow = document.getElementById("arrow");

dropdownBtn.addEventListener("click", () => {
    const isVisible = dropdownList.style.display === "block";
    dropdownList.style.display = isVisible ? "none" : "block";
    arrow.style.transform = isVisible ? "rotate(0deg)" : "rotate(180deg)";
});
                            
document.addEventListener("click", (e) => {
    if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
        dropdownList.style.display = "none";
        arrow.style.transform = "rotate(0deg)";
    }
});