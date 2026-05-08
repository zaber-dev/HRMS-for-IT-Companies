import * as React from "react"
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"

interface FormSelectProps {
    name: string
    defaultValue?: string
    value?: string
    onValueChange?: (value: string) => void
    placeholder?: string
    required?: boolean
    children: React.ReactNode
    className?: string
    id?: string
}

function FormSelect({
    name,
    defaultValue,
    value,
    onValueChange,
    placeholder,
    required,
    children,
    className,
    id,
}: FormSelectProps) {
    // Check if controlled mode (value prop is defined)
    const isControlled = value !== undefined
    
    // For uncontrolled mode, track internal state
    const [internalValue, setInternalValue] = React.useState(defaultValue || "")

    const handleValueChange = (newValue: string) => {
        if (!isControlled) {
            setInternalValue(newValue)
        }
        onValueChange?.(newValue)
    }

    // Determine the value to pass to Select - always string or undefined, never switches type
    const selectValue = isControlled 
        ? (value || undefined)  // controlled: use value prop
        : (internalValue || undefined)  // uncontrolled: use internal state

    return (
        <>
            <Select
                value={selectValue}
                onValueChange={handleValueChange}
                required={required}
            >
                <SelectTrigger id={id} className={className}>
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {children}
                </SelectContent>
            </Select>
            <input type="hidden" name={name} value={isControlled ? (value || "") : internalValue} />
        </>
    )
}

export { FormSelect, SelectItem, SelectContent, SelectTrigger, SelectValue }
