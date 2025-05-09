import domReady from "@wordpress/dom-ready";
import { TabPanel } from "@wordpress/components";
import { RuleForm } from "./components/rule-form";
import { createRoot } from "@wordpress/element";
import { _n } from "@wordpress/i18n";
import { SettingsForm } from "./components/settings-form";
import { Notices } from "./components/notices";

const App = () => {
    return (
        <div className="als-drw-container">
            <Notices />
            <TabPanel
                tabs={[
                    {
                        name: "rules",
                        title: _n("Discount Rules", "als-discount-rules-for-woocommerce"),
                        className: "tab-rules"
                    },
                    {
                        name: "settings",
                        title: _n("Settings", "als-discount-rules-for-woocommerce"),
                        className: "tab-settings"
                    }
                ]}
            >
                {
                    tab => {
                        let child = false

                        switch (tab.name) {
                            case 'rules':
                                child = <RuleForm />
                                break
                            
                            case 'settings':
                                child = <SettingsForm />
                                break

                            default:
                                child = false
                                break
                        }

                        return child
                    }
                }
            </TabPanel>
        </div>
    )
}

domReady(() => {
    const root = createRoot(
        document.getElementById('als-drw-settings-root')
    )

    root.render(<App />)
})