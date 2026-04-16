<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>我相信柯文哲 線上簽名系統</title>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { font-family: sans-serif; text-align: center; background: #e0e0e0; margin: 0; padding: 10px; }
        
        .container { 
            width: 100%; max-width: 800px; margin: auto; background: white; 
            padding: 15px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }

        h2 { font-size: 1.1rem; color: #444; margin: 10px 0; }

        .color-selector { display: flex; justify-content: center; gap: 15px; margin-bottom: 15px; }
        .color-dot {
            width: 35px; height: 35px; border-radius: 50%; cursor: pointer;
            border: 3px solid transparent; transition: 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .color-dot.active { border-color: #333; transform: scale(1.15); }

        .sig-wrapper {
            position: relative; width: 100%; height: 250px; background: #fff;
            border: 2px dashed #bbb; border-radius: 8px; touch-action: none;
        }
        #signature-pad { width: 100%; height: 100%; }

        .canvas-container-outer {
            width: 100%; 
            margin-top: 15px;
            display: none;
            background: #fff;
            border: 3px solid #000000; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            line-height: 0;
            position: relative;
            touch-action: none; /* 防止瀏覽器預設縮放干擾 */
        }

        .controls { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin: 15px 0; }
        
        button { 
            flex: 1; min-width: 120px; padding: 12px; font-size: 16px; 
            font-weight: bold; cursor: pointer; border: none; border-radius: 6px; 
        }
        button#clear { background: #e74c3c; color: white; }
        button#confirm-sign { background: #3498db; color: white; }
        button#save-final { background: #27ae60; color: white; width: 100%; margin-top: 10px; }
        
        button:active { opacity: 0.8; }
        hr { border: 0; border-top: 1px solid #eee; margin: 20px 0; }
        
        img {
  max-width: 100%;
  max-height: 100%;
  width: auto;
  height: auto;
  object-fit: contain;
  display: block;
}
    </style>
</head>
<body>

<div class="container">
    <h2><center><img src="font-image.png"></center><BR>第一步：選擇顏色並簽名</h2>
    
    <div class="color-selector">
        <div class="color-dot active" style="background: #000000;" data-color="#000000"></div>
        <div class="color-dot" style="background: #0000FF;" data-color="#0000FF"></div>
        <div class="color-dot" style="background: #FF0000;" data-color="#FF0000"></div>
        <div class="color-dot" style="background: #006400;" data-color="#006400"></div>
        <div class="color-dot" style="background: #000080;" data-color="#000080"></div>
        <div class="color-dot" style="background: #8B0000;" data-color="#8B0000"></div>
    </div>

    <div class="sig-wrapper">
        <canvas id="signature-pad"></canvas>
    </div>
    
    <div class="controls">
        <button id="clear">清除重簽</button>
        <button id="confirm-sign">簽好了</button>
    </div>

    <div id="step-2-area" style="display:none;">
        <hr>
        <h2>第二步：移動到您想要放的位置</h2>
        <div class="canvas-container-outer" id="canvas-holder">
            <canvas id="final-canvas"></canvas>
        </div>
        <button id="save-final">確認並存檔</button>
        
        <button id="reset-bg" style="background: #95a5a6; color: white; width: 100%; margin-top: 10px;">
    畫面已滿，使用新底圖
</button>
    </div>
</div>

<script>
    // --- 簽名板邏輯 ---
    const sigCanvas = document.getElementById('signature-pad');
    const signaturePad = new SignaturePad(sigCanvas, {
        backgroundColor: 'rgba(255, 255, 255, 0)',
        penColor: '#000000'
    });

    const dots = document.querySelectorAll('.color-dot');
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            dots.forEach(d => d.classList.remove('active'));
            dot.classList.add('active');
            signaturePad.penColor = dot.getAttribute('data-color');
        });
    });

    function resizeSigCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        sigCanvas.width = sigCanvas.offsetWidth * ratio;
        sigCanvas.height = sigCanvas.offsetHeight * ratio;
        sigCanvas.getContext("2d").scale(ratio, ratio);
        signaturePad.clear();
    }
    window.addEventListener("resize", resizeSigCanvas);
    resizeSigCanvas();

    // --- Fabric 畫布邏輯 ---
    const fabricCanvas = new fabric.Canvas('final-canvas', {
        stopContextMenu: true,
        fireRightClick: true
    });

    // 雙指縮放變數
    let initialDistance = 0;
    let initialScale = 1;
    let initialAngle = 0;

    // 輔助函式：計算兩指距離與角度
    function getTouchInfo(touches) {
        const dx = touches[0].clientX - touches[1].clientX;
        const dy = touches[0].clientY - touches[1].clientY;
        const distance = Math.sqrt(dx * dx + dy * dy);
        const angle = Math.atan2(dy, dx) * (180 / Math.PI);
        return { distance, angle };
    }

    // 監聽原生觸控事件來支援 Fabric 物件縮放
    const upperCanvas = fabricCanvas.upperCanvasEl;
    upperCanvas.addEventListener('touchstart', (e) => {
        if (e.touches.length === 2) {
            const activeObj = fabricCanvas.getActiveObject();
            if (activeObj) {
                const info = getTouchInfo(e.touches);
                initialDistance = info.distance;
                initialScale = activeObj.scaleX;
                initialAngle = activeObj.angle - info.angle;
            }
        }
    }, { passive: false });

    upperCanvas.addEventListener('touchmove', (e) => {
        if (e.touches.length === 2) {
            e.preventDefault(); // 防止頁面滾動
            const activeObj = fabricCanvas.getActiveObject();
            if (activeObj) {
                const info = getTouchInfo(e.touches);
                // 縮放
                const newScale = initialScale * (info.distance / initialDistance);
                activeObj.set({
                    scaleX: newScale,
                    scaleY: newScale,
                    angle: initialAngle + info.angle
                });
                activeObj.setCoords();
                fabricCanvas.renderAll();
            }
        }
    }, { passive: false });

    function loadBackground() {
        const bgUrl = 'background.png?t=' + new Date().getTime();
        fabric.Image.fromURL(bgUrl, (img) => {
            if (!img) {
                alert("找不到 background.png，請確認檔案是否存在於 NAS 資料夾中。");
                return;
            }
            const holder = document.getElementById('canvas-holder');
            const containerWidth = holder.offsetWidth - 6; 
            const scaleFactor = containerWidth / img.width;

            fabricCanvas.setWidth(containerWidth);
            fabricCanvas.setHeight(img.height * scaleFactor);

            fabricCanvas.setBackgroundImage(img, fabricCanvas.renderAll.bind(fabricCanvas), {
                scaleX: scaleFactor,
                scaleY: scaleFactor
            });
        });
    }

    document.getElementById('confirm-sign').addEventListener('click', () => {
        if (signaturePad.isEmpty()) return alert("請先簽名");
        
        document.getElementById('step-2-area').style.display = 'block';
        document.getElementById('canvas-holder').style.display = 'block';
        
        loadBackground();

        const signData = signaturePad.toDataURL('image/png');
        fabric.Image.fromURL(signData, (img) => {
            img.scale(0.4); 
            img.set({
                left: 50, top: 50,
                cornerColor: '#3498db', cornerSize: 32, // 加大點擊區
                transparentCorners: false,
                borderColor: '#3498db'
            });
            fabricCanvas.add(img);
            fabricCanvas.setActiveObject(img);
        });
        
        setTimeout(() => {
            window.scrollTo({ top: document.getElementById('step-2-area').offsetTop, behavior: 'smooth' });
        }, 300);
    });

    document.getElementById('clear').addEventListener('click', () => signaturePad.clear());

document.getElementById('save-final').addEventListener('click', () => {
        // 1. 取得底圖原始物件（用於獲取原始尺寸）
        const bgImage = fabricCanvas.backgroundImage;
        
        if (!bgImage) {
            alert("尚未載入底圖");
            return;
        }

        // 2. 計算還原比例 (原始寬度 / 目前畫布寬度)
        // bgImage.width 是圖片原始寬度，fabricCanvas.width 是網頁顯示寬度
        const exportMultiplier = bgImage.width / fabricCanvas.width;

        // 3. 使用 multiplier 還原解析度輸出
        const finalData = fabricCanvas.toDataURL({ 
            format: 'png', 
            quality: 1,
            multiplier: exportMultiplier // 自動計算倍率，確保輸出為 1920 (或原始寬度)
        });
        
        fetch('save.php', {
            method: 'POST',
            body: JSON.stringify({ image: finalData }),
            headers: { 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert("已完成簽名！");
                location.reload();
            } else {
                alert("錯誤：" + data.message);
            }
        });
    });
    
    document.getElementById('reset-bg').addEventListener('click', () => {
    if (!confirm("確定要清除目前所有簽名並重設底圖嗎？(目前的畫面會存入備份)")) return;

    fetch('save.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'reset' }), // 傳送 reset 指令
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert("底圖已重設！");
            location.reload();
        } else {
            alert("重設失敗：" + data.message);
        }
    });
});
</script>

</body>
</html>