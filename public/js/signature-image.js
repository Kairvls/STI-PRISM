/**
 * Signature upload helpers: strip paper / light backgrounds so ink overlays printed names.
 * Tolerates grayish bond paper; rejects uploads that don't look like a usable signature photo.
 */
(function (global) {
    'use strict';

    var MAX_EDGE = 1400;
    var GUIDE =
        'Use a clear dark-ink signature on light / white bond paper (flat photo, avoid heavy shadows).';

    function loadImage(src) {
        return new Promise(function (resolve, reject) {
            var img = new Image();
            img.onload = function () { resolve(img); };
            img.onerror = function () { reject(new Error('Could not read that signature image.')); };
            // Same-origin library URLs; data URLs ignore this.
            try { img.crossOrigin = 'anonymous'; } catch (e) {}
            img.src = src;
        });
    }

    function readFileAsDataUrl(file) {
        return new Promise(function (resolve, reject) {
            if (!file) return resolve(null);
            var reader = new FileReader();
            reader.onload = function () { resolve(reader.result); };
            reader.onerror = function () { reject(new Error('Could not read that signature file.')); };
            reader.readAsDataURL(file);
        });
    }

    function brightnessOf(r, g, b) {
        // Perceived luminance — better for gray paper + colored ink.
        return (0.2126 * r) + (0.7152 * g) + (0.0722 * b);
    }

    function estimatePaperBrightness(data, width, height) {
        var histogram = new Array(256);
        var i;
        for (i = 0; i < 256; i++) histogram[i] = 0;

        // Prefer border samples (usually paper, not ink).
        function sample(x, y) {
            var idx = ((y * width) + x) * 4;
            var a = data[idx + 3];
            if (a < 8) return;
            var b = Math.max(0, Math.min(255, Math.round(brightnessOf(data[idx], data[idx + 1], data[idx + 2]))));
            histogram[b] += 1;
        }

        var margin = Math.max(2, Math.floor(Math.min(width, height) * 0.04));
        var x;
        var y;
        for (x = 0; x < width; x += 2) {
            for (y = 0; y < margin; y += 2) sample(x, y);
            for (y = Math.max(margin, height - margin); y < height; y += 2) sample(x, y);
        }
        for (y = margin; y < height - margin; y += 2) {
            for (x = 0; x < margin; x += 2) sample(x, y);
            for (x = Math.max(margin, width - margin); x < width; x += 2) sample(x, y);
        }

        var total = 0;
        for (i = 0; i < 256; i++) total += histogram[i];

        // Fallback: full-image histogram if borders were empty.
        if (total < 80) {
            for (i = 0; i < 256; i++) histogram[i] = 0;
            total = 0;
            for (i = 0; i < data.length; i += 16) {
                if (data[i + 3] < 8) continue;
                var bb = Math.max(0, Math.min(255, Math.round(brightnessOf(data[i], data[i + 1], data[i + 2]))));
                histogram[bb] += 1;
                total += 1;
            }
        }

        if (total < 1) return 220;

        // ~78th percentile ≈ typical light paper tone in the photo.
        var target = total * 0.78;
        var running = 0;
        for (i = 0; i < 256; i++) {
            running += histogram[i];
            if (running >= target) return i;
        }
        return 220;
    }

    /**
     * @param {string} dataUrlOrUrl
     * @param {{validate?: boolean}} options
     * @returns {Promise<string>} cleaned PNG data URL
     */
    function removePaperBackground(dataUrlOrUrl, options) {
        var opts = options || {};
        var validate = opts.validate !== false;

        if (!dataUrlOrUrl) {
            return Promise.reject(new Error(GUIDE));
        }

        return loadImage(dataUrlOrUrl).then(function (img) {
            var srcW = img.naturalWidth || img.width || 0;
            var srcH = img.naturalHeight || img.height || 0;
            if (srcW < 1 || srcH < 1) {
                throw new Error(GUIDE);
            }

            var scale = Math.min(1, MAX_EDGE / Math.max(srcW, srcH));
            var width = Math.max(1, Math.round(srcW * scale));
            var height = Math.max(1, Math.round(srcH * scale));
            var totalPixels = width * height;

            var canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            var ctx = canvas.getContext('2d', { willReadFrequently: true });
            if (!ctx) {
                throw new Error('Could not process that signature image.');
            }

            ctx.clearRect(0, 0, width, height);
            ctx.drawImage(img, 0, 0, width, height);

            var imageData = ctx.getImageData(0, 0, width, height);
            var data = imageData.data;
            var paper = estimatePaperBrightness(data, width, height);

            if (validate && paper < 150) {
                throw new Error(
                    'Paper looks too dark. ' + GUIDE
                );
            }

            // Adaptive cutoffs from estimated paper tone (works for grayish bond paper).
            var hardCut = Math.max(168, Math.min(245, paper - 8));
            var softCut = Math.max(130, Math.min(hardCut - 18, paper - 42));
            var inkKeep = Math.max(90, softCut - 25);

            var minX = width;
            var minY = height;
            var maxX = -1;
            var maxY = -1;
            var inkPixels = 0;
            var opaquePixels = 0;

            for (var i = 0; i < data.length; i += 4) {
                var r = data[i];
                var g = data[i + 1];
                var b = data[i + 2];
                var a = data[i + 3];
                if (a < 8) {
                    data[i + 3] = 0;
                    continue;
                }

                var brightness = brightnessOf(r, g, b);
                var maxC = Math.max(r, g, b);
                var minC = Math.min(r, g, b);
                var sat = maxC - minC;
                var alpha;

                // Dark / saturated ink (black, blue pens) stays solid.
                if (brightness <= inkKeep || (sat > 35 && brightness < hardCut - 10)) {
                    alpha = 255;
                } else if (brightness >= hardCut) {
                    alpha = 0;
                } else if (brightness >= softCut) {
                    alpha = Math.round(255 * ((hardCut - brightness) / Math.max(1, hardCut - softCut)));
                } else {
                    // Between softCut and inkKeep: mostly ink with slight fade for soft edges.
                    alpha = Math.round(200 + (55 * ((softCut - brightness) / Math.max(1, softCut - inkKeep))));
                    alpha = Math.max(0, Math.min(255, alpha));
                }

                data[i + 3] = alpha;
                if (alpha > 20) {
                    opaquePixels += 1;
                }
                if (alpha > 40) {
                    inkPixels += 1;
                    var px = (i / 4) % width;
                    var py = Math.floor((i / 4) / width);
                    if (px < minX) minX = px;
                    if (py < minY) minY = py;
                    if (px > maxX) maxX = px;
                    if (py > maxY) maxY = py;
                }
            }

            var opaqueRatio = opaquePixels / Math.max(1, totalPixels);
            if (validate) {
                if (inkPixels < 80 || maxX < minX || maxY < minY) {
                    throw new Error(
                        'No clear signature ink found. ' + GUIDE
                    );
                }
                // Still mostly opaque ⇒ paper was not removed (wrong photo / too dark).
                if (opaqueRatio > 0.42) {
                    throw new Error(
                        'Could not clear the paper background. ' + GUIDE
                    );
                }
            } else if (inkPixels < 30 || maxX < minX || maxY < minY) {
                // Soft path (e.g. re-clean a drawn sig): leave original alone.
                return dataUrlOrUrl;
            }

            ctx.putImageData(imageData, 0, 0);

            var pad = Math.max(2, Math.round(Math.min(width, height) * 0.03));
            var cropX = Math.max(0, minX - pad);
            var cropY = Math.max(0, minY - pad);
            var cropW = Math.min(width - cropX, (maxX - minX + 1) + pad * 2);
            var cropH = Math.min(height - cropY, (maxY - minY + 1) + pad * 2);

            var out = document.createElement('canvas');
            out.width = Math.max(1, cropW);
            out.height = Math.max(1, cropH);
            var outCtx = out.getContext('2d');
            if (!outCtx) {
                return canvas.toDataURL('image/png');
            }
            outCtx.clearRect(0, 0, out.width, out.height);
            outCtx.drawImage(canvas, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);
            return out.toDataURL('image/png');
        });
    }

    /**
     * Read an image file, clean paper background, validate for signature use.
     * @param {File} file
     * @returns {Promise<string>}
     */
    function fromFile(file) {
        return readFileAsDataUrl(file).then(function (dataUrl) {
            if (!dataUrl) {
                throw new Error(GUIDE);
            }
            return removePaperBackground(dataUrl, { validate: true });
        });
    }

    /**
     * Best-effort clean for already-saved / drawn signatures (no hard reject).
     * @param {string} src
     * @returns {Promise<string>}
     */
    function cleanForOverlay(src) {
        if (!src) return Promise.resolve('');
        return removePaperBackground(src, { validate: false }).catch(function () {
            return src;
        });
    }

    global.PrismSignatureImage = {
        GUIDE: GUIDE,
        removePaperBackground: removePaperBackground,
        cleanForOverlay: cleanForOverlay,
        fromFile: fromFile,
        readFileAsDataUrl: readFileAsDataUrl,
    };
})(typeof window !== 'undefined' ? window : this);
