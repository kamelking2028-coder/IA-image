document.addEventListener("DOMContentLoaded", () => {

    document.getElementById("generate").onclick = async () => {
        const prompt = document.getElementById("prompt").value;

        const formData = new FormData();
        formData.append("prompt", prompt);

        const response = await fetch("https://ia-image.site.je/api.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        // HuggingFace renvoie du base64
        const imageBase64 = data.image_base64;

        document.getElementById("result").src =
            "data:image/png;base64," + imageBase64;
    };

});

