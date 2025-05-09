import React, { useCallback } from "react";
import AsyncSelect from "react-select/async";
import { __ } from "@wordpress/i18n";
import { useFetchProductCategories } from "../hooks";

const CategorySelector = ({ selectedCategories, setSelectedCategories }) => {
    // Memoize the function to prevent unnecessary re-fetches
    const loadOptions = useCallback(useFetchProductCategories, []);

    const handleSelectChange = (selectedOptions) => {
        setSelectedCategories(selectedOptions);
    };

    return (
        <div>
            <AsyncSelect
                isMulti
                cacheOptions
                loadOptions={loadOptions}
                value={selectedCategories}
                placeholder={__('Search Categories', 'als-discount-rules-for-woocommerce')}
                onChange={handleSelectChange}
            />
        </div>
    );
};

export default CategorySelector;
