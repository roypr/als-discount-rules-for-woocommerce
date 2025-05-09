import { useState } from "@wordpress/element";
import { useFetchSettings, useSaveSettings } from "../hooks";
import { __ } from "@wordpress/i18n";
import { Button, Flex, FlexBlock, FlexItem, SelectControl, Spinner, TextControl, ToggleControl } from "@wordpress/components";
import { cancelCircleFilled, pencil, plus, published, trash } from "@wordpress/icons";

import ProductSelector from "./product-selector";
import ColorPickerControl from "./color-picker-control";
import CategorySelector from "./category-selector";

const RuleForm = () => {
    const { rules, setRules, others, setOthers, loadingSettings } = useFetchSettings();
    
    const { saveSettings, savingSettings } = useSaveSettings(setRules, setOthers);

    const [active, setActive] = useState(null);
    const [error, setError] = useState("");

    const handleAddNew = () => {
        setActive({
            title: `${__("Rule", "als-discount-rules-for-woocommerce")} ${rules.length + 1}`,
            discount_type: "percent",
            discount_on: "",
            amount: "0",
            min_order: "0",
            is_active: 'no',
            inc_products: [],
            inc_categories: [],
            ex_products: [],
            ex_categories: []
        });
        setError(""); // Clear any previous error
    };

    const handleEdit = (index) => {
        setActive({ ...rules[index], index });
        setError(""); // Clear any previous error
    };

    const handleDelete = (index) => {
        const updatedRules = rules.filter((_, i) => i !== index);
        saveSettings(updatedRules, others);
        // setRules(updatedRules);
        setActive(null)
    };

    const handleCancel = () => {
        setActive(null);
        setError(""); // Clear any previous error
    };

    const handleSave = () => {
        const requiredFields = ["title", "discount_type", "discount_on", "amount", "is_active"];
        const missingFields = requiredFields.filter((field) => !active[field]?.trim());

        if (missingFields.length > 0) {
            setError(__("The following fields are required:", "als-discount-rules-for-woocommerce") + ` ${missingFields.join(", ")}`);

            return;
        }

        let updatedRules;

        if (active.hasOwnProperty("index")) {
            updatedRules = [...rules];
            updatedRules[active.index] = { ...active };
            delete updatedRules[active.index].index;
        } else {
            updatedRules = [...rules, { ...active }];
        }

        saveSettings(updatedRules, others);
        // setRules(updatedRules)
        setActive(null);
        setError("");
    };

    const handleToggleActive = (index, newValue) => {
        const updatedRules = [...rules];
        updatedRules[index] = { ...updatedRules[index], is_active: newValue ? "yes" : "no" };
        saveSettings(updatedRules, others);
        // setRules(updatedRules);
    };

    return (
        <div style={{ padding: "20px", border: "1px solid #ddd", borderRadius: "4px" }}>
            {(loadingSettings || savingSettings) && (
                <div className="als-drw-loading-overlay">
                    <Spinner />
                </div>
            )}

            <Flex justify="flex-end" style={{ marginBottom: "10px" }}>
                <Button variant="primary" onClick={handleAddNew} icon={plus}>
                    {__("Add New", "als-discount-rules-for-woocommerce")}
                </Button>
            </Flex>

            <Flex direction="column">
                {/* Table Header */}
                <Flex align="center" style={{ fontWeight: "bold", borderBottom: "2px solid #000", padding: "10px 0", background: "#f4f4f4" }}>
                    <FlexBlock style={{ flex: 3, paddingLeft: '12px' }}>{__("Label", "als-discount-rules-for-woocommerce")}</FlexBlock>
                    <FlexBlock style={{ flex: 1 }}>{__("Discount", "als-discount-rules-for-woocommerce")}</FlexBlock>
                    <FlexBlock style={{ flex: 1 }}>{__("On", "als-discount-rules-for-woocommerce")}</FlexBlock>
                    <FlexBlock style={{ flex: 1 }}>{__("Active", "als-discount-rules-for-woocommerce")}</FlexBlock>
                    <FlexItem style={{ flex: 2, textAlign: 'center' }}>{__("Actions", "als-discount-rules-for-woocommerce")}</FlexItem>
                </Flex>

                {rules.length > 0 ? (
                    rules.map((rule, index) => (
                        <Flex key={index} align="center" style={{ marginBottom: "10px", borderBottom: "1px solid #ddd", padding: "10px 0" }}>
                            <FlexBlock style={{ flex: 3, paddingLeft: '12px' }}>
                                <strong>{rule.title || ''}</strong>
                            </FlexBlock>
                            <FlexBlock style={{ flex: 1 }}>
                                {(rule?.discount_type === 'percent') ? (
                                    <span>
                                        {rule?.amount || 0}%
                                    </span>
                                ) : (
                                    <span>
                                        {alsDrw.currencySymbol}{rule?.amount || 0}
                                    </span>
                                )}
                            </FlexBlock>
                            <FlexBlock style={{ flex: 1 }}>
                                {(rule.discount_on == "total") ? __("Total", "als-discount-rules-for-woocommerce") : __("Product", "als-discount-rules-for-woocommerce")}
                            </FlexBlock>
                            <FlexBlock style={{ flex: 1 }}>
                                <ToggleControl
                                    checked={rule.is_active === "yes"}
                                    onChange={(newValue) => handleToggleActive(index, newValue)}
                                />
                            </FlexBlock>
                            <FlexBlock style={{ flex: 2 }}>
                                <Flex justify="end">
                                    <FlexItem>
                                        <Button variant="secondary" onClick={() => handleEdit(index)} icon={pencil}>
                                            {__("Edit", "als-discount-rules-for-woocommerce")}
                                        </Button>
                                    </FlexItem>
                                    <FlexItem>
                                        <Button variant="secondary" isDestructive={true} onClick={() => handleDelete(index)} icon={trash}>
                                            {__("Delete", "als-discount-rules-for-woocommerce")}
                                        </Button>
                                    </FlexItem>
                                </Flex>
                            </FlexBlock>
                            
                        </Flex>
                    ))
                ) : (
                    <p>
                        {__("No rules found", "als-discount-rules-for-woocommerce")}
                    </p>
                )}


                {active && (
                    <div style={{ marginTop: "20px", padding: "20px", border: "1px solid #ddd", borderRadius: "6px" }}>
                        <h3 style={{ marginBottom: "15px", fontSize: "1.2rem", fontWeight: "bold" }}>
                            {active.hasOwnProperty("index") ? __("Edit Rule", "als-discount-rules-for-woocommerce") : __("Add New Rule", "als-discount-rules-for-woocommerce")}
                        </h3>

                        {error && <p style={{ color: "red", marginBottom: "10px" }}>{error}</p>}

                        <Flex wrap style={{ gap: "10px", marginBottom: "15px" }}>
                            <FlexBlock style={{ flex: "1 1 30%" }}>
                                <TextControl
                                    label={ __("Label", "als-discount-rules-for-woocommerce")}
                                    value={active.title}
                                    onChange={(value) => setActive({ ...active, title: value })}
                                />
                            </FlexBlock>
                            <FlexBlock style={{ flex: "1 1 30%" }}>
                                <SelectControl
                                    label={__("Discount On", "als-discount-rules-for-woocommerce")}
                                    value={active.discount_on}
                                    options={[
                                        { label: __("Select option", "als-discount-rules-for-woocommerce"), value: "" },
                                        { label: __("Total", "als-discount-rules-for-woocommerce"), value: "total" },
                                        { label: __("Product", "als-discount-rules-for-woocommerce") , value: "product" },
                                    ]}
                                    onChange={(value) => setActive({ ...active, discount_on: value })}
                                />
                            </FlexBlock>
                            <FlexBlock style={{ flex: "1 1 30%" }}>
                                <SelectControl
                                    label={__("Activate Rule", "als-discount-rules-for-woocommerce")}
                                    value={active.is_active}
                                    options={[
                                        { label: __("Select option", "als-discount-rules-for-woocommerce"), value: "" },
                                        { label: __("Yes", "als-discount-rules-for-woocommerce"), value: "yes" },
                                        { label: __("No", "als-discount-rules-for-woocommerce"), value: "no" },
                                    ]}
                                    onChange={(value) => setActive({ ...active, is_active: value })}
                                />
                            </FlexBlock>
                        </Flex>

                        <Flex wrap style={{ gap: "10px", marginBottom: "15px" }}>
                            <FlexBlock style={{ flex: "1 1 30%" }}>
                                <SelectControl
                                    label={__("Discount Type", "als-discount-rules-for-woocommerce")}
                                    value={active.discount_type}
                                    options={[
                                        { label: __("Select a type", "als-discount-rules-for-woocommerce"), value: "" },
                                        { label: __("Percent", "als-discount-rules-for-woocommerce"), value: "percent" },
                                        { label: __("Flat", "als-discount-rules-for-woocommerce"), value: "flat" },
                                    ]}
                                    onChange={(value) => setActive({ ...active, discount_type: value })}
                                />
                            </FlexBlock>
                            <FlexBlock style={{ flex: "1 1 30%" }}>
                                <TextControl
                                    label={__("Discount Amount", "als-discount-rules-for-woocommerce")}
                                    value={active.amount}
                                    onChange={(value) => setActive({ ...active, amount: value })}
                                />
                            </FlexBlock>
                            {/* Conditionally show Minimum Order if discount_on is 'total' */}
                            {active.discount_on === 'total' && (
                                <>
                                    <FlexBlock style={{ flex: "1 1 30%" }}>
                                        <TextControl
                                            label={__("Minimum Order", "als-discount-rules-for-woocommerce")}
                                            value={active.min_order}
                                            onChange={(value) => setActive({ ...active, min_order: value })}
                                        />
                                    </FlexBlock>

                                </>
                                
                                
                            )}
                        </Flex>

                        {/* Conditionally show Categories and Products if discount_on is 'product' */}
                        {active.discount_on === 'product' && (
                            <>
                                <Flex wrap style={{ gap: "10px", marginBottom: "15px" }}>
                                    <FlexBlock style={{ flex: "1 1 40%" }}>
                                        <fieldset style={{ marginBottom: "15px" }}>
                                            <legend style={{ fontWeight: "bold", marginBottom: "10px" }}>
                                                {__("Include Categories", "als-discount-rules-for-woocommerce")}
                                            </legend>
                                            <CategorySelector
                                                selectedCategories={active.inc_categories}
                                                setSelectedCategories={(newValues) => setActive({ ...active, inc_categories: newValues })}
                                            />
                                            
                                        </fieldset>
                                    </FlexBlock>
                                    <FlexBlock style={{ flex: "1 1 40%" }}>
                                        <fieldset style={{ marginBottom: "15px" }}>
                                            <legend style={{ fontWeight: "bold", marginBottom: "10px" }}>
                                                {__("Exclude Categories", "als-discount-rules-for-woocommerce")}
                                            </legend>
                                            <CategorySelector
                                                selectedCategories={active.ex_categories}
                                                setSelectedCategories={(newValues) => setActive({ ...active, ex_categories: newValues })}
                                            />
                                            
                                        </fieldset>
                                    </FlexBlock>
                                </Flex>
                                <legend style={{ fontWeight: "bold", marginBottom: "10px" }}>
                                    {__("Include Products", "als-discount-rules-for-woocommerce")}
                                </legend>
                                <Flex wrap style={{ gap: "10px", marginBottom: "15px" }}>
                                    <FlexBlock style={{ flex: "1 1 100%" }}>
                                        <ProductSelector
                                            selectedProducts={active.inc_products}
                                            setSelectedProducts={(newValues) => setActive({ ...active, inc_products: newValues })}
                                        />
                                    </FlexBlock>
                                </Flex>
                                <legend style={{ fontWeight: "bold", marginBottom: "10px" }}>
                                    {__("Exclude Products", "als-discount-rules-for-woocommerce")}
                                </legend>
                                <Flex wrap style={{ gap: "10px", marginBottom: "15px" }}>
                                    <FlexBlock style={{ flex: "1 1 100%" }}>
                                        <ProductSelector
                                            selectedProducts={active.ex_products}
                                            setSelectedProducts={(newValues) => setActive({ ...active, ex_products: newValues })}
                                        />
                                    </FlexBlock>
                                </Flex>
                            </>
                        )}

                        {/* Save and Cancel Buttons */}
                        <Flex justify="flex-end" style={{ marginTop: "10px" }}>
                            <Button variant="primary" icon={published} style={{ marginRight: "10px" }} onClick={handleSave}>
                                {__("Save", "als-discount-rules-for-woocommerce")}
                            </Button>
                            <Button variant="secondary" icon={cancelCircleFilled} onClick={handleCancel}>
                                {__("Cancel", "als-discount-rules-for-woocommerce")}
                            </Button>
                        </Flex>
                    </div>
                )}
                        
            </Flex>
        </div>
    )
};

export { RuleForm };