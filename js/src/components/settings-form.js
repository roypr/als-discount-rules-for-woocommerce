import { useState } from "@wordpress/element";
import { useFetchSettings, useSaveSettings } from "../hooks";
import { Button, Flex, FlexBlock, SelectControl, Spinner, TextControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { published } from "@wordpress/icons";

const SettingsForm = () => {
    const { rules, setRules, others, setOthers, loadingSettings } = useFetchSettings();
    const { saveSettings, savingSettings } = useSaveSettings();

    const [error, setError] = useState("");

    const handleSave = () => {
        const requiredFields = ["show_to", "apply_rule"];
        const missingFields = requiredFields.filter((field) => !others[field]?.trim());

        if (missingFields.length > 0) {
            setError(`The following fields are required: ${missingFields.join(", ")}`);
            return;
        }

        saveSettings(rules, others)
        setError("")
    }

    const ruleOptions = rules.map( rule => {
        return {
            label : rule.title,
            value : escAttr(rule.title)
        }
    })

    return (
        <div style={{ padding: "20px", border: "1px solid #ddd", borderRadius: "4px" }}>
            {(loadingSettings || savingSettings) && (
                <div className="als-drw-loading-overlay">
                    <Spinner />
                </div>
            )}

            <div style={{ marginTop: "20px", padding: "20px", border: "1px solid #ddd", borderRadius: "6px" }}>
                <h3 style={{ marginBottom: "15px", fontSize: "1.2rem", fontWeight: "bold" }}>
                    {__("Edit Settings", "als-drw")}
                </h3>
                
                {error && <p style={{ color: "red", marginBottom: "10px" }}>{error}</p>}

                <Flex wrap style={{ gap: "10px", marginBottom: "15px" }}>
                    <FlexBlock style={{ flex: "1 1 45%" }}>
                        <SelectControl
                            label={__("If multiple rules match", "als-drw")}
                            value={others.apply_rule}
                            options={[
                                { label: __("Select option", "als-drw"), value: "" },
                                { label: __("Apply lowest discount", "als-drw"), value: "lowest" },
                                { label: __("Apply highest discount", "als-drw"), value: "highest" },
                            ]}
                            onChange={(value) => setOthers({...others, apply_rule : value})}
                        />
                    </FlexBlock>
                    <FlexBlock style={{ flex: "1 1 45%" }}>
                        <SelectControl
                            label={__("Discount applies to", "als-drw")}
                            value={others.show_to}
                            options={[
                                { label: __("Select option", "als-drw"), value: "" },
                                { label: __("Logged in users only", "als-drw"), value: "logged_in" },
                                { label: __("Everyone", "als-drw"), value: "all" },
                            ]}
                            onChange={(value) => setOthers({ ...others, show_to: value })}
                        />
                    </FlexBlock>
                </Flex>

                <Flex wrap style={{ gap: "10px", marginBottom: "15px" }}>
                    <FlexBlock style={{ flex: "1 1 45%" }}>
                        <TextControl
                            label={__("From Text", "als-drw")}
                            value={others.from_text}
                            onChange={(value) => setOthers({ ...others, from_text: value })}
                        />
                    </FlexBlock>
                    <FlexBlock style={{ flex: "1 1 45%" }}>
                        <SelectControl
                            label={__("First choice rule", "als-drw")}
                            value={others.exclusive_rule}
                            options={[
                                { label: __("None", "als-drw"), value: "" },
                                ...ruleOptions
                            ]}
                            onChange={(value) => setOthers({ ...others, exclusive_rule: value })}
                        />
                    </FlexBlock>
                </Flex>


                <Flex justify="flex-end" style={{ marginTop: '20px' }}>
                    <Button variant="primary" icon={published} onClick={handleSave}>
                        {__("Save", "als-drw")}
                    </Button>
                </Flex>

            </div>
        </div>
    )
}

const escAttr = (str) => {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
}

export {SettingsForm}