document.getElementById("generateBtn").onclick = async () => {
    const prompt = document.getElementById("prompt").value;

    const response = await fetch("https://api.openai.com/v1/images/generations", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Authorization": "Bearer TA_CLE_API"
        },
        body: JSON.stringify({
            model: "gpt-image-1",
            prompt: prompt,
            size: "1024x1024"
        })
    });

    const data = await response.json();
    const url = data.data[0].url;

    document.getElementById("resultImage").src = url;
};
