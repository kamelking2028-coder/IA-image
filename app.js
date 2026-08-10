document.getElementById("generateBtn").onclick = async () => {
    const prompt = document.getElementById("prompt").value;

    const formData = new FormData();
    formData.append("prompt", prompt);

    const response = await fetch("https://TONSITE/api.php", {
        method: "POST",
        body: formData
    });

    const data = await response.json();
    const url = data.data[0].url;

    document.getElementById("resultImage").src = url;
};

