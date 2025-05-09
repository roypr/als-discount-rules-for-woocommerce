import React, { useCallback } from "react";
import AsyncSelect from "react-select/async";
import { __ } from "@wordpress/i18n";
import { useFetchProducts } from "../hooks";

const ProductSelector = ({ selectedProducts, setSelectedProducts }) => {
    // Memoize the function to prevent unnecessary re-fetches
    const loadOptions = useCallback(useFetchProducts, []);

    const handleSelectChange = (selectedOptions) => {
        setSelectedProducts(selectedOptions);
    };

    return (
        <div>
            <AsyncSelect
                isMulti
                cacheOptions
                loadOptions={loadOptions}
                value={selectedProducts}
                placeholder={__('Search Products', 'als-discount-rules-for-woocommerce')}
                onChange={handleSelectChange}
            />
        </div>
    );
};

export default ProductSelector;
