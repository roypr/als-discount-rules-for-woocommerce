import { useEffect, useState } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { useDispatch } from "@wordpress/data";
import { store as noticesStore } from "@wordpress/notices";
import { __ } from "@wordpress/i18n";

const useFetchSettings = () => {
    const [rules, setRules] = useState([]);
    const [others, setOthers] = useState({});

    const [loadingSettings, setLoadingSettings] = useState(true);
    const { createErrorNotice } = useDispatch(noticesStore);

    useEffect(() => {
        setLoadingSettings(true);
        apiFetch({ path: "/wp/v2/settings" })
            .then((settings) => {
                let als_drw = settings.als_drw || {
                    rules : [],
                    others: {
                        apply_rule: 'lowest',
                        show_to: 'all',
                        exclusive_rule : '',
                        from_text : ''
                    }
                }

                setRules(als_drw.rules);
                setOthers(als_drw.others);
            })
            .catch(() => {
                createErrorNotice(__("Failed to load settings.", "als-drw"));
            })
            .finally(() => {
                setLoadingSettings(false);
            });
    }, []);

    return { rules, setRules, others, setOthers, loadingSettings };
};

const useSaveSettings = (setRules, setOthers) => {
    const { createSuccessNotice, createErrorNotice } = useDispatch(noticesStore);
    const [savingSettings, setSavingSettings] = useState(false);

    const saveSettings = async (newRules, newOthers) => {
        setSavingSettings(true);
        try {
            try {
                await apiFetch({
                    path: "/wp/v2/settings",
                    method: "POST",
                    data: {
                        als_drw: {
                            rules: newRules,
                            others: newOthers,
                        },
                    },
                });
                createSuccessNotice(__("Settings saved successfully!", "als-drw"));
                setRules(newRules);
                setOthers(newOthers);
            } catch (error) {
                if (error.data && error.data.params) {
                    Object.entries(error.data.params).forEach(([field, message]) => {
                        createErrorNotice(`${field}: ${message}`);
                    });
                } else {
                    createErrorNotice(__("Failed to save settings.", "als-drw"));
                }
            }
        } finally {
            setSavingSettings(false);
        }
    };

    return { saveSettings, savingSettings };
};

const useFetchProductCategories = async (inputValue = "") => {
    try {
        const categories = await apiFetch({
            path: `/wc/v3/products/categories?search=${encodeURIComponent(inputValue)}&per_page=10`,
        });

        return categories.map((category) => ({
            value: category.id,
            label: category.name,
        }));
    } catch (error) {
        console.error("Failed to fetch product categories:", error);
        return [];
    }
};

const useFetchProducts = async (inputValue) => {
    if (!inputValue) return [];

    try {
        // Fetch parent products
        const products = await apiFetch({
            path: `/wc/v3/products?search=${inputValue}&per_page=10`,
        });

        // Fetch variations for variable products
        const variationPromises = products
            .filter((product) => product.type === "variable")
            .map(async (product) => {
                const variations = await apiFetch({
                    path: `/wc/v3/products/${product.id}/variations`,
                });
                return variations.map((variation) => ({
                    value: variation.id,
                    label: `${product.name} - ${variation.attributes.map(attr => attr.option).join(", ")}`,
                    parent_id : product.id,
                    product_type : product.type
                }));
            });

        const variations = (await Promise.all(variationPromises)).flat();

        // Combine products & variations
        return [
            ...products.map((product) => ({
                value: product.id,
                label: product.name,
                parent_id : 0,
                product_type : product.type 
            })),
            ...variations,
        ];
    } catch (error) {
        console.error("Error fetching products:", error);
        return [];
    }
};

export {useFetchSettings, useSaveSettings, useFetchProductCategories, useFetchProducts};
