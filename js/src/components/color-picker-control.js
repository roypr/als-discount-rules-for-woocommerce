import React, { useState } from "react";
import { SketchPicker } from "react-color";

const ColorPickerControl = ({ label, color, onChange }) => {
    const [showPicker, setShowPicker] = useState(false);

    const handleClick = () => setShowPicker(!showPicker);
    const handleClose = () => setShowPicker(false);
    const handleChange = (newColor) => onChange(newColor.hex);

    return (
        <div style={{ marginBottom: "20px", position: "relative" }}>
            <label style={{ display: "block", fontWeight: "bold", marginBottom: "6px" }}>
                {label}
            </label>
            <div
                style={{
                    width: "36px",
                    height: "20px",
                    borderRadius: "4px",
                    background: color || "#000000",
                    border: "1px solid #ccc",
                    cursor: "pointer",
                }}
                onClick={handleClick}
            />
            {showPicker && (
                <div style={{ position: "absolute", zIndex: "999" }}>
                    <div
                        style={{
                            position: "fixed",
                            top: 0,
                            left: 0,
                            bottom: 0,
                            right: 0,
                        }}
                        onClick={handleClose}
                    />
                    <SketchPicker
                        color={color}
                        onChangeComplete={handleChange}
                        disableAlpha
                    />
                </div>
            )}
        </div>
    );
};

export default ColorPickerControl;
